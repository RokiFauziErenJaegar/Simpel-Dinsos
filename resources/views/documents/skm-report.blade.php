<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan SKM {{ $label }}</title>
    <style>
        @page { margin: 1.5cm 1.5cm 2cm 1.5cm; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10pt; color: #1f2937; line-height: 1.5; }
        h1, h2, h3 { color: #1E4D8C; margin: 0 0 8px; }
        .header { border-bottom: 3px double #1E4D8C; padding-bottom: 10px; margin-bottom: 14px; display: table; width: 100%; }
        .header .left { display: table-cell; width: 80px; vertical-align: middle; }
        .header .right { display: table-cell; vertical-align: middle; }
        .header h1 { font-size: 12pt; margin: 0; }
        .header h2 { font-size: 16pt; margin: 0; }
        .title { background: #1E4D8C; color: white; padding: 8px 12px; margin: 14px 0 10px; border-radius: 4px; font-size: 11pt; font-weight: bold; }
        .kpi-grid { display: table; width: 100%; border-collapse: separate; border-spacing: 6px; }
        .kpi-grid .cell { display: table-cell; background: #f1f5f9; padding: 10px; border-radius: 6px; width: 25%; }
        .kpi-grid .cell .value { font-size: 18pt; font-weight: bold; color: #1E4D8C; }
        .kpi-grid .cell .label { font-size: 8pt; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; }
        table.data { width: 100%; border-collapse: collapse; margin-top: 8px; }
        table.data th, table.data td { padding: 6px 8px; border-bottom: 1px solid #e2e8f0; text-align: left; font-size: 9pt; }
        table.data th { background: #f1f5f9; font-weight: bold; }
        table.data td.num { text-align: right; }
        .signature { margin-top: 30px; text-align: right; padding-right: 30px; }
        .signature .name { font-weight: bold; text-decoration: underline; margin-top: 60px; }
        .footer-line { margin-top: 18px; padding-top: 6px; border-top: 1px solid #cbd5e1; font-size: 8pt; color: #64748b; text-align: center; }
    </style>
</head>
<body>

<div class="header">
    <div class="left">
        <div style="width:60px;height:60px;border:2px solid #1E4D8C;border-radius:50%;text-align:center;line-height:56px;color:#1E4D8C;font-weight:bold;font-size:8pt;">PRINGSEWU</div>
    </div>
    <div class="right">
        <h1>PEMERINTAH KABUPATEN PRINGSEWU</h1>
        <h2>DINAS SOSIAL</h2>
        <div style="font-size:8pt;color:#64748b;">Jl. Dr. dr. Sugiri Syarief · Komplek Perkantoran Pemda Pringsewu · 35372</div>
    </div>
</div>

<h2 style="text-align:center;font-size:14pt;margin:0;">LAPORAN SURVEI KEPUASAN MASYARAKAT (SKM)</h2>
<p style="text-align:center;color:#64748b;margin-top:2px;">Periode: {{ $label }}</p>
<p style="text-align:center;font-size:9pt;color:#64748b;">Sesuai Permenpan RB No. 14 Tahun 2017 tentang Pedoman Penyusunan SKM.</p>

<div class="title">RINGKASAN</div>
<div class="kpi-grid">
    <div class="cell">
        <div class="value">{{ number_format($stats['total']) }}</div>
        <div class="label">Total Responden</div>
    </div>
    <div class="cell">
        <div class="value">{{ $stats['index'] !== null ? number_format($stats['index'], 2) : '—' }}</div>
        <div class="label">Indeks (IKM)</div>
    </div>
    <div class="cell">
        <div class="value" style="font-size:12pt;">{{ $stats['category'] }}</div>
        <div class="label">Mutu Pelayanan</div>
    </div>
    <div class="cell">
        <div class="value" style="font-size:11pt;">{{ $from->format('d/m/y') }}<br>{{ $to->format('d/m/y') }}</div>
        <div class="label">Rentang</div>
    </div>
</div>

<div class="title">NILAI PER UNSUR</div>
<table class="data">
    <thead><tr><th>No</th><th>Unsur Pelayanan</th><th style="text-align:right;">Nilai (0–100)</th><th style="text-align:right;">Jml Jawaban</th></tr></thead>
    <tbody>
        @foreach($stats['per_unsur'] as $u)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $u['label'] }}</td>
                <td class="num">{{ $u['score'] !== null ? number_format($u['score'], 2) : '—' }}</td>
                <td class="num">{{ $u['count'] }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

<div class="title">SEBARAN PER LOKASI PELAYANAN</div>
<table class="data">
    <thead><tr><th>Lokasi</th><th style="text-align:right;">Responden</th><th style="text-align:right;">Indeks (IKM)</th></tr></thead>
    <tbody>
        @foreach($stats['per_location'] as $loc)
            <tr>
                <td>{{ $loc['label'] }}</td>
                <td class="num">{{ $loc['total'] }}</td>
                <td class="num">{{ $loc['index'] !== null ? number_format($loc['index'], 2) : '—' }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

@if(count($stats['latest_saran']))
    <div class="title">SARAN &amp; MASUKAN TERBARU</div>
    <table class="data">
        <thead><tr><th>Tanggal</th><th>Responden</th><th>Saran</th></tr></thead>
        <tbody>
            @foreach($stats['latest_saran'] as $s)
                <tr>
                    <td>{{ $s['at']?->translatedFormat('d M Y') }}</td>
                    <td>{{ $s['name'] ?: 'Anonim' }}</td>
                    <td>{{ $s['saran'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif

<div class="signature">
    <div>Pringsewu, {{ now()->translatedFormat('d F Y') }}</div>
    <div>Kepala Dinas Sosial Kabupaten Pringsewu</div>
    <div class="name">{{ $signer }}</div>
</div>

<div class="footer-line">
    Dokumen dihasilkan otomatis oleh SIMPEL DINSOS · {{ now()->translatedFormat('d F Y H:i') }} WIB
</div>

</body>
</html>
