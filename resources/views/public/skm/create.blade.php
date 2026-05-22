@extends('layouts.public')
@section('title', 'Survei Kepuasan Masyarakat')
@section('content')

<section class="max-w-2xl mx-auto px-4 md:px-6 py-10">
    <h1 class="text-2xl font-bold text-slate-900">Survei Kepuasan Masyarakat</h1>
    <p class="text-slate-600 text-sm mt-1">9 unsur sesuai Permenpan RB No. 14/2017. Hanya 1 menit, masukan Anda sangat berarti.</p>

    <div class="mt-4 card-elev p-4 bg-blue-50 text-blue-900 text-sm">
        <strong>{{ $application->code }}</strong> · {{ $application->serviceType->name }}<br>
        Penerima: {{ $application->beneficiary_name }}
    </div>

    <form method="post" action="{{ route('skm.store', ['code' => $application->code]) }}" class="mt-6 space-y-4">
        @csrf

        @php
            $items = [
                'persyaratan' => 'Kesesuaian persyaratan layanan',
                'prosedur' => 'Kemudahan prosedur',
                'waktu' => 'Kecepatan waktu pelayanan',
                'biaya' => 'Kewajaran biaya (gratis)',
                'produk' => 'Kesesuaian produk dengan janji',
                'kompetensi' => 'Kompetensi petugas',
                'perilaku' => 'Keramahan & perilaku petugas',
                'sarana' => 'Kenyamanan sarana & prasarana',
                'penanganan_pengaduan' => 'Penanganan pengaduan',
            ];
            $labels = ['Sangat Buruk', 'Buruk', 'Cukup', 'Baik', 'Sangat Baik'];
        @endphp

        @foreach($items as $key => $label)
            <div class="card-elev p-4">
                <div class="font-medium text-slate-900 mb-3">{{ $loop->iteration }}. {{ $label }}</div>
                <div class="grid grid-cols-5 gap-2">
                    @for($i = 1; $i <= 5; $i++)
                        <label class="cursor-pointer text-center">
                            <input type="radio" name="{{ $key }}" value="{{ $i }}" required class="peer sr-only">
                            <div class="px-2 py-3 border-2 border-slate-200 rounded-lg text-xs font-medium peer-checked:border-[color:var(--brand)] peer-checked:bg-blue-50 peer-checked:text-[color:var(--brand)]">
                                <div class="text-2xl">{{ ['😣','😟','😐','😊','😍'][$i-1] }}</div>
                                <div class="mt-1">{{ $labels[$i-1] }}</div>
                            </div>
                        </label>
                    @endfor
                </div>
            </div>
        @endforeach

        <div class="card-elev p-4 space-y-3">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Saran perbaikan (opsional)</label>
                <textarea name="saran" rows="3" class="w-full px-3 py-2 rounded-lg border border-slate-200"></textarea>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <input name="respondent_name" placeholder="Nama (opsional)" class="px-3 py-2 rounded-lg border border-slate-200">
                <input name="respondent_contact" placeholder="HP/Email (opsional)" class="px-3 py-2 rounded-lg border border-slate-200">
            </div>
        </div>

        <button type="submit" class="btn-primary w-full justify-center">Kirim Penilaian →</button>
    </form>
</section>

@endsection
