<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Bulanan {{ $month->format('M Y') }}</title>
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
        .badge-ok { background: #ecfdf5; color: #047857; padding: 1px 6px; border-radius: 4px; font-size: 8pt; }
        .badge-warn { background: #fef3c7; color: #92400e; padding: 1px 6px; border-radius: 4px; font-size: 8pt; }
        .badge-bad { background: #fee2e2; color: #b91c1c; padding: 1px 6px; border-radius: 4px; font-size: 8pt; }
        .narrative { background: #f8fafc; border-left: 3px solid #1E4D8C; padding: 10px 14px; margin: 10px 0; font-size: 10pt; text-align: justify; }
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

<h2 style="text-align:center;font-size:14pt;margin:0;">LAPORAN BULANAN PELAYANAN PUBLIK</h2>
<p style="text-align:center;color:#64748b;margin-top:2px;">Periode: {{ $month->translatedFormat('F Y') }}</p>
<p style="text-align:center;font-size:9pt;color:#64748b;">Disampaikan kepada Bupati Pringsewu sebagai bahan evaluasi kinerja.</p>

<div class="title">RINGKASAN KPI</div>
<div class="kpi-grid">
    <div class="cell">
        <div class="value">{{ number_format($total) }}</div>
        <div class="label">Total Pengajuan</div>
    </div>
    <div class="cell">
        <div class="value">{{ number_format($completed) }}</div>
        <div class="label">Selesai</div>
    </div>
    <div class="cell">
        <div class="value">{{ $on_time_pct }}%</div>
        <div class="label">Ketepatan SLA</div>
    </div>
    <div class="cell">
        <div class="value">{{ $skm_index ?? '—' }}</div>
        <div class="label">Indeks Kepuasan ({{ $skm_count }} resp)</div>
    </div>
</div>

<div class="title">PERFORMA PER LAYANAN</div>
<table class="data">
    <thead>
        <tr>
            <th style="width:6%;">Kode</th>
            <th>Jenis Layanan</th>
            <th style="width:10%; text-align:right;">Diajukan</th>
            <th style="width:10%; text-align:right;">Selesai</th>
            <th style="width:14%;">Ketepatan SLA</th>
        </tr>
    </thead>
    <tbody>
        @forelse($per_service as $r)
            <tr>
                <td>{{ $r['code'] }}</td>
                <td>{{ \Illuminate\Support\Str::limit($r['name'], 55) }}</td>
                <td style="text-align:right;">{{ $r['total'] }}</td>
                <td style="text-align:right;">{{ $r['completed'] }}</td>
                <td>
                    @if($r['sla_pct'] === null)
                        <span class="badge-warn">—</span>
                    @elseif($r['sla_pct'] >= 90)
                        <span class="badge-ok">{{ $r['sla_pct'] }}%</span>
                    @elseif($r['sla_pct'] >= 70)
                        <span class="badge-warn">{{ $r['sla_pct'] }}%</span>
                    @else
                        <span class="badge-bad">{{ $r['sla_pct'] }}%</span>
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="5" style="text-align:center;color:#94a3b8;padding:14px;">Belum ada pengajuan tercatat pada periode ini.</td></tr>
        @endforelse
    </tbody>
</table>

<div class="title">SEBARAN PELAYANAN PER LOKASI</div>
<table class="data">
    <thead><tr><th>Lokasi Pelayanan</th><th style="text-align:right;">Total Pengajuan</th><th style="text-align:right;">Selesai</th></tr></thead>
    <tbody>
        @foreach($per_location as $r)
            <tr>
                <td>{{ $r['label'] }}</td>
                <td style="text-align:right;">{{ number_format($r['total']) }}</td>
                <td style="text-align:right;">{{ number_format($r['completed']) }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

<div class="title">PENGADUAN MASYARAKAT</div>
<p style="margin:6px 0;">Selama periode ini diterima <strong>{{ $complaints }}</strong> pengaduan, dengan <strong>{{ $complaints_resolved }}</strong> telah ditindaklanjuti dan selesai.</p>

<div class="title">RINGKASAN EKSEKUTIF</div>
<div class="narrative">
    {!! nl2br(e($narrative)) !!}
</div>

<div class="signature">
    <div>Pringsewu, {{ now()->translatedFormat('d F Y') }}</div>
    <div>Kepala Dinas Sosial Kabupaten Pringsewu,</div>
    <div class="name">{{ $signer }}</div>
    <div style="font-size:9pt;">Pembina Utama Muda</div>
</div>

<div class="footer-line">
    Laporan ini dihasilkan otomatis oleh SIMPEL DINSOS · {{ now()->translatedFormat('d F Y H:i') }} · Data diambil dari sistem pelayanan publik resmi.
</div>

</body>
</html>
