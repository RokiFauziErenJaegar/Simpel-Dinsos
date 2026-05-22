<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>{{ $docNumber }}</title>
    <style>
        @page { margin: 1.5cm 2cm 2cm 2cm; }
        body { font-family: 'DejaVu Serif', 'Times New Roman', serif; font-size: 12pt; color: #000; line-height: 1.5; }
        .header { display: table; width: 100%; border-bottom: 4px double #000; padding-bottom: 8px; margin-bottom: 6px; }
        .header .left { display: table-cell; width: 90px; vertical-align: middle; }
        .header .right { display: table-cell; vertical-align: middle; text-align: center; }
        .header .logo {
            width: 80px; height: 80px;
            border: 2px solid #1E4D8C; border-radius: 50%;
            display: inline-block; text-align: center;
            font-weight: bold; color: #1E4D8C;
            line-height: 76px; font-size: 11pt;
        }
        .header h1 { margin: 0; font-size: 14pt; font-weight: bold; letter-spacing: 0.5px; }
        .header h2 { margin: 2px 0; font-size: 16pt; font-weight: bold; }
        .header .address { font-size: 10pt; margin-top: 4px; }
        .title { text-align: center; margin: 30px 0 8px; }
        .title .label { font-weight: bold; text-decoration: underline; text-transform: uppercase; letter-spacing: 2px; font-size: 13pt; }
        .title .number { font-size: 11pt; margin-top: 4px; }
        .body { margin-top: 30px; text-align: justify; }
        .body p { margin: 0 0 8px; }
        table.data { width: 100%; margin: 12px 0; }
        table.data td { padding: 2px 4px; vertical-align: top; }
        table.data td.label { width: 32%; }
        table.data td.colon { width: 2%; }
        .signature { margin-top: 50px; width: 100%; }
        .signature table { width: 100%; }
        .signature td { vertical-align: top; }
        .signature .right { width: 50%; text-align: center; position: relative; }
        .signature .place { margin-bottom: 4px; }
        .signature .role { font-weight: normal; margin-bottom: 8px; }
        .signature .name { font-weight: bold; text-decoration: underline; margin-top: 4px; }
        .signature .nip { font-size: 11pt; }
        .signature .sig-area {
            position: relative; height: 110px; margin: 4px auto;
        }
        .signature .stamp-img {
            position: absolute;
            left: 50%; top: 0;
            transform: translateX(-65%);
            width: 130px; height: 130px;
            opacity: 0.7;
        }
        .signature .sig-img {
            position: absolute;
            left: 50%; top: 12px;
            transform: translateX(-35%);
            width: 160px; height: 80px;
        }
        .qr-block {
            position: absolute; bottom: 1.5cm; left: 2cm;
            font-size: 9pt; color: #555;
        }
        .qr-block .qr { width: 90px; height: 90px; vertical-align: middle; }
        .qr-block .text { display: inline-block; vertical-align: middle; margin-left: 10px; }
        .footer-note { margin-top: 40px; padding-top: 8px; border-top: 1px solid #ccc; font-size: 9pt; color: #555; text-align: center; }
    </style>
</head>
<body>

<div class="header">
    <div class="left">
        <div class="logo">PRINGSEWU</div>
    </div>
    <div class="right">
        <h1>PEMERINTAH KABUPATEN PRINGSEWU</h1>
        <h2>DINAS SOSIAL</h2>
        <div class="address">
            Jl. Dr. dr. Sugiri Syarief, Komplek Perkantoran Pemerintah Daerah Kabupaten Pringsewu, Kode Pos 35372<br>
            Telp/WA: 0822-6986-7911 · Email: pringsewudinsos@gmail.com
        </div>
    </div>
</div>

<div class="title">
    <div class="label">{{ $service->output }}</div>
    <div class="number">Nomor: {{ $docNumber }}</div>
</div>

<div class="body">
    <p>Yang bertanda tangan di bawah ini, Kepala Dinas Sosial Kabupaten Pringsewu, dengan ini menerangkan bahwa:</p>

    <table class="data">
        <tr><td class="label">Nama</td><td class="colon">:</td><td><strong>{{ $app->beneficiary_name }}</strong></td></tr>
        @if($app->beneficiary_nik)
        <tr><td class="label">NIK</td><td>:</td><td>{{ $app->beneficiary_nik }}</td></tr>
        @endif
        <tr><td class="label">Pemohon</td><td>:</td><td>{{ $app->applicant?->name ?? '—' }}</td></tr>
        <tr><td class="label">Keperluan</td><td>:</td><td>{{ $app->purpose ?? '-' }}</td></tr>
        <tr><td class="label">Layanan</td><td>:</td><td>{{ $service->name }}</td></tr>
        <tr><td class="label">Kode Pengajuan</td><td>:</td><td>{{ $app->code }}</td></tr>
    </table>

    <p>
        Berdasarkan verifikasi yang telah dilakukan terhadap berkas dan kelengkapan data,
        yang bersangkutan dinyatakan <strong>memenuhi syarat</strong> sesuai standar pelayanan
        Dinas Sosial Kabupaten Pringsewu yang ditetapkan dalam Maklumat Pelayanan
        Nomor 920/460/D.04/X/2023.
    </p>

    <p>
        Surat ini diterbitkan untuk dapat dipergunakan sebagaimana mestinya.
        Keaslian dokumen dapat diverifikasi melalui pemindaian QR Code yang tercantum
        atau dengan mengunjungi tautan verifikasi di bawah.
    </p>
</div>

<div class="signature">
    <table>
        <tr>
            <td class="right">
                <div class="place">Pringsewu, {{ now()->translatedFormat('d F Y') }}</div>
                <div class="role">{{ $signer->jabatan_full ?: 'Kepala Dinas Sosial Kabupaten Pringsewu' }},</div>

                <div class="sig-area">
                    @if(! empty($stampDataUri))
                        <img class="stamp-img" src="{{ $stampDataUri }}" alt="Stempel">
                    @endif
                    @if(! empty($signatureDataUri))
                        <img class="sig-img" src="{{ $signatureDataUri }}" alt="Tanda Tangan">
                    @endif
                </div>

                <div class="name">{{ $signer->name }}</div>
                <div class="nip">
                    {{ $signer->pangkat ?: 'Pembina Utama Muda' }}<br>
                    NIP {{ $signer->nip ?: '19671022 199803 2 005' }}
                </div>
            </td>
        </tr>
    </table>
</div>

<div class="qr-block">
    <img class="qr" src="data:image/svg+xml;base64,{{ $qrSvg }}" alt="QR Verifikasi">
    <div class="text">
        Pindai QR atau buka:<br>
        <strong>{{ $verifyUrl }}</strong><br>
        <em>Dokumen ini sah secara hukum walau tanpa tanda tangan basah.</em>
    </div>
</div>

</body>
</html>
