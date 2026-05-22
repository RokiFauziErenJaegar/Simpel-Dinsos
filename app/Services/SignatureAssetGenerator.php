<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Storage;

/**
 * Buat aset stempel + tanda tangan SVG default untuk demo.
 * Di produksi, Kadis upload file PNG hasil scan via halaman Profil.
 */
class SignatureAssetGenerator
{
    public function ensureDefaults(User $kadis): void
    {
        if (! $kadis->signature_path || ! Storage::disk('public')->exists($kadis->signature_path)) {
            $sig = $this->generateSignatureSvg($kadis->name);
            $path = "signatures/user-{$kadis->id}.svg";
            Storage::disk('public')->put($path, $sig);
            $kadis->signature_path = $path;
        }

        if (! $kadis->stamp_path || ! Storage::disk('public')->exists($kadis->stamp_path)) {
            $stamp = $this->generateStampSvg();
            $path = "stamps/user-{$kadis->id}.svg";
            Storage::disk('public')->put($path, $stamp);
            $kadis->stamp_path = $path;
        }

        $kadis->jabatan_full = $kadis->jabatan_full ?: 'Kepala Dinas Sosial Kabupaten Pringsewu';
        $kadis->nip = $kadis->nip ?: '19671022 199803 2 005';
        $kadis->pangkat = $kadis->pangkat ?: 'Pembina Utama Muda';

        $kadis->save();
    }

    protected function generateSignatureSvg(string $name): string
    {
        // SVG sederhana yang menyerupai tanda tangan cursive
        $initial = strtoupper(substr($name, 0, 1));
        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="220" height="100" viewBox="0 0 220 100">
  <g fill="none" stroke="#1a3a8a" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
    <path d="M 20 65 Q 30 25, 50 55 T 80 50 Q 95 70, 110 45 T 145 55 Q 160 30, 175 60 T 200 50"/>
    <path d="M 40 75 L 195 75" stroke-width="1.2"/>
    <path d="M 25 70 Q 40 50, 55 60 Q 70 75, 85 55"/>
  </g>
  <text x="110" y="95" font-family="Brush Script MT, cursive" font-size="22" font-style="italic" fill="#1a3a8a" text-anchor="middle">{$initial}H</text>
</svg>
SVG;
    }

    protected function generateStampSvg(): string
    {
        // Stempel bundar dengan tulisan resmi
        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="160" height="160" viewBox="0 0 160 160">
  <defs>
    <path id="topArc" d="M 30 80 A 50 50 0 0 1 130 80"/>
    <path id="bottomArc" d="M 30 80 A 50 50 0 0 0 130 80"/>
  </defs>
  <circle cx="80" cy="80" r="68" fill="none" stroke="#7b1f2e" stroke-width="3"/>
  <circle cx="80" cy="80" r="58" fill="none" stroke="#7b1f2e" stroke-width="1.5"/>
  <circle cx="80" cy="80" r="20" fill="none" stroke="#7b1f2e" stroke-width="1.5"/>
  <text fill="#7b1f2e" font-family="serif" font-size="9" font-weight="bold" letter-spacing="1.5">
    <textPath href="#topArc" startOffset="50%" text-anchor="middle">PEMERINTAH KABUPATEN PRINGSEWU</textPath>
  </text>
  <text fill="#7b1f2e" font-family="serif" font-size="9" font-weight="bold" letter-spacing="1.5">
    <textPath href="#bottomArc" startOffset="50%" text-anchor="middle">★ DINAS SOSIAL ★</textPath>
  </text>
  <text x="80" y="85" font-family="serif" font-size="11" font-weight="bold" fill="#7b1f2e" text-anchor="middle">DINSOS</text>
  <text x="80" y="148" font-family="serif" font-size="8" fill="#7b1f2e" text-anchor="middle">PRINGSEWU</text>
</svg>
SVG;
    }
}
