@extends('layouts.public')
@section('title', 'Sudah Mengisi')
@section('content')
<section class="max-w-xl mx-auto px-4 md:px-6 py-20 text-center">
    <div class="w-20 h-20 mx-auto rounded-full bg-blue-100 text-blue-600 flex items-center justify-center text-4xl">✔</div>
    <h1 class="mt-6 text-2xl font-bold text-slate-900">Anda Sudah Mengisi Survei Ini</h1>
    <p class="mt-2 text-slate-600">Terima kasih atas penilaian Anda sebelumnya untuk pengajuan {{ $application->code }}.</p>
    <div class="mt-6"><a href="{{ route('home') }}" class="btn-primary">Beranda</a></div>
</section>
@endsection
