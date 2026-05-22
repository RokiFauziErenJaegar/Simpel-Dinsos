@extends('layouts.public')
@section('title', 'Terima Kasih')
@section('content')

<section class="max-w-xl mx-auto px-4 md:px-6 py-20 text-center">
    <div class="w-20 h-20 mx-auto rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center text-4xl">✓</div>
    <h1 class="mt-6 text-2xl font-bold text-slate-900">Terima Kasih Atas Penilaian Anda</h1>
    <p class="mt-2 text-slate-600">Masukan Anda membantu kami terus memperbaiki kualitas pelayanan Dinas Sosial Kabupaten Pringsewu.</p>
    <div class="mt-6">
        <a href="{{ route('home') }}" class="btn-primary">Kembali ke Beranda</a>
    </div>
</section>
@endsection
