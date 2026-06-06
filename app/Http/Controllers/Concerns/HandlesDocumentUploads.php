<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

/**
 * Penanganan unggah berkas yang konsisten di semua form (warga, perbaikan,
 * operator pekon): notifikasi ramah bahasa Indonesia saat ukuran berkas
 * terlalu besar — termasuk kasus berkas melebihi limit PHP
 * (upload_max_filesize / post_max_size) yang biasanya gagal diam-diam.
 */
trait HandlesDocumentUploads
{
    /** Batas ukuran per berkas dalam kilobyte — selaras dengan rule `max:2048` pada validasi. */
    protected int $maxUploadKb = 2048;

    /**
     * Hentikan request lebih awal dengan pesan yang jelas bila ada berkas yang
     * ukurannya melebihi batas. Panggil SEBELUM $request->validate() agar warga
     * mendapat notifikasi yang menyebut nama berkas & ukurannya, bukan pesan
     * teknis bawaan ("The docs.0 field must not be greater than 2048 kilobytes").
     */
    protected function guardOversizedUploads(Request $request): void
    {
        $maxBytes = $this->maxUploadKb * 1024;
        $maxLabel = $this->humanFileSize($maxBytes);

        // Kasus 1: total body melebihi post_max_size → PHP membuang seluruh data,
        // sehingga $_FILES & input kosong walau browser sebenarnya mengirim berkas.
        $contentLength = (int) $request->server('CONTENT_LENGTH', 0);
        if ($contentLength > 0 && count($_FILES) === 0 && count($request->all()) === 0) {
            throw ValidationException::withMessages([
                'docs' => 'Total ukuran berkas yang diunggah terlalu besar. '
                    ."Batas maksimal {$maxLabel} per berkas — mohon perkecil ukuran berkas lalu coba lagi.",
            ]);
        }

        // Kasus 2: per-berkas. Cek semua input file (docs[], replace_docs[], new_docs[], dst.).
        $errors = [];
        foreach ($this->flattenUploadedFiles($request->allFiles()) as $field => $file) {
            $exceedsIniLimit = $file->getError() === UPLOAD_ERR_INI_SIZE;
            $exceedsAppLimit = $file->isValid() && $file->getSize() > $maxBytes;

            if (! $exceedsIniLimit && ! $exceedsAppLimit) {
                continue;
            }

            $name = $file->getClientOriginalName() ?: 'berkas';
            $errors[$field][] = $exceedsAppLimit
                ? "Berkas \"{$name}\" berukuran {$this->humanFileSize($file->getSize())} — "
                    ."melebihi batas maksimal {$maxLabel}. Mohon perkecil ukurannya lalu unggah ulang."
                : "Berkas \"{$name}\" terlalu besar — melebihi batas maksimal {$maxLabel}. "
                    .'Mohon perkecil ukurannya lalu unggah ulang.';
        }

        if ($errors) {
            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * Pesan validasi berbahasa Indonesia untuk aturan berkas — diteruskan sebagai
     * argumen kedua $request->validate([...], $this->documentUploadMessages()).
     */
    protected function documentUploadMessages(): array
    {
        $maxLabel = $this->humanFileSize($this->maxUploadKb * 1024);

        return [
            '*.max' => "Ukuran berkas terlalu besar. Maksimal {$maxLabel} per berkas.",
            '*.mimes' => 'Format berkas tidak didukung. Gunakan JPG, PNG, atau PDF.',
            '*.file' => 'Berkas gagal diunggah. Mohon coba unggah ulang.',
            '*.uploaded' => "Berkas gagal diunggah karena ukurannya terlalu besar (maksimal {$maxLabel}).",
            'docs.*.required' => 'Berkas wajib diunggah.',
        ];
    }

    /**
     * Ratakan array berkas bersarang (docs[], replace_docs[id], new_docs[]) menjadi
     * pasangan "field.kunci" => UploadedFile, melompati slot kosong.
     *
     * @param  array<string, mixed>  $files
     * @return array<string, UploadedFile>
     */
    protected function flattenUploadedFiles(array $files, string $prefix = ''): array
    {
        $flat = [];
        foreach ($files as $key => $value) {
            $field = $prefix === '' ? (string) $key : "{$prefix}.{$key}";
            if (is_array($value)) {
                $flat += $this->flattenUploadedFiles($value, $field);
            } elseif ($value instanceof UploadedFile) {
                $flat[$field] = $value;
            }
        }

        return $flat;
    }

    /** Ukuran byte menjadi label ramah, mis. 5242880 → "5 MB", 1572864 → "1,5 MB". */
    protected function humanFileSize(int $bytes): string
    {
        if ($bytes >= 1024 * 1024) {
            $mb = $bytes / (1024 * 1024);

            return rtrim(rtrim(number_format($mb, 1, ',', '.'), '0'), ',').' MB';
        }

        return max(1, (int) round($bytes / 1024)).' KB';
    }
}
