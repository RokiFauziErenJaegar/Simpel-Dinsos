@extends('layouts.public')
@section('title', 'Tambah Akun Petugas')
@section('content')

<section class="max-w-md mx-auto px-4 md:px-6 py-12">

    {{-- Akun yang sedang aktif: ditegaskan supaya petugas sadar sesi lamanya
         tidak hilang saat menambahkan akun baru. --}}
    <div class="card-elev p-4 flex items-center gap-3">
        <div class="w-10 h-10 rounded-full brand-gradient text-white flex items-center justify-center font-bold shrink-0">
            {{ mb_strtoupper(mb_substr($current->name, 0, 1)) }}
        </div>
        <div class="min-w-0">
            <p class="text-xs text-slate-500">Sedang aktif</p>
            <p class="font-semibold text-slate-900 truncate">{{ $current->name }}</p>
        </div>
        <a href="/admin" class="ml-auto text-sm font-semibold text-[color:var(--brand)] shrink-0">Kembali</a>
    </div>

    @if(session('success'))
        <div class="mt-4 p-3 bg-emerald-50 text-emerald-700 text-sm rounded-lg">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="mt-4 p-3 bg-amber-50 text-amber-800 text-sm rounded-lg">{{ session('error') }}</div>
    @endif

    <div class="card-elev p-8 mt-4">
        <h1 class="text-2xl font-bold text-slate-900">Tambah Akun Petugas</h1>
        <p class="text-slate-600 text-sm mt-1">
            Masuk dengan akun petugas lain tanpa mengeluarkan akun yang sedang aktif.
            Setelah ditambahkan, Anda bisa berpindah akun kapan saja tanpa kata sandi lagi.
        </p>

        @if ($errors->any())
            <div class="mt-4 p-3 bg-rose-50 text-rose-700 text-sm rounded-lg">{{ $errors->first() }}</div>
        @endif

        <form method="post" action="{{ route('account.add.store') }}" class="mt-6 space-y-4">
            @csrf

            <div>
                <label for="email" class="block text-sm font-medium text-slate-700 mb-1">Email petugas</label>
                <input
                    id="email"
                    name="email"
                    type="email"
                    required
                    autofocus
                    autocomplete="username"
                    value="{{ old('email', $prefill) }}"
                    placeholder="petugas@dinsos.go.id"
                    class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-[color:var(--brand)] focus:ring-2 focus:ring-blue-100 outline-none">
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-slate-700 mb-1">Kata sandi</label>
                <input
                    id="password"
                    name="password"
                    type="password"
                    required
                    autocomplete="current-password"
                    class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-[color:var(--brand)] focus:ring-2 focus:ring-blue-100 outline-none">
            </div>

            <button type="submit" class="btn-primary w-full justify-center">Tambahkan & Masuk →</button>
        </form>
    </div>

    @if($switchable->isNotEmpty())
        <div class="card-elev p-6 mt-4">
            <h2 class="font-bold text-slate-900">Akun siap dipakai</h2>
            <p class="text-xs text-slate-500 mt-0.5">Sudah terverifikasi di sesi ini — pindah tanpa kata sandi.</p>

            <div class="mt-4 space-y-2">
                @foreach($switchable as $account)
                    <div class="flex items-center gap-3 p-3 rounded-xl border border-slate-200">
                        <div class="w-9 h-9 rounded-full bg-slate-100 text-slate-600 flex items-center justify-center font-bold text-sm shrink-0">
                            {{ mb_strtoupper(mb_substr($account->name, 0, 1)) }}
                        </div>
                        <div class="min-w-0">
                            <p class="font-medium text-slate-900 text-sm truncate">{{ $account->name }}</p>
                            <p class="text-xs text-slate-500 truncate">{{ $account->role->label() }}</p>
                        </div>
                        <div class="ml-auto flex items-center gap-1 shrink-0">
                            <form method="post" action="{{ route('account.switch', $account->id) }}">
                                @csrf
                                <button class="text-sm font-semibold text-[color:var(--brand)] px-3 py-1.5 rounded-lg hover:bg-blue-50">
                                    Pindah
                                </button>
                            </form>
                            <form method="post" action="{{ route('account.forget', $account->id) }}">
                                @csrf
                                <button title="Lepas akun dari perangkat ini"
                                        class="text-sm text-slate-400 px-2 py-1.5 rounded-lg hover:bg-rose-50 hover:text-rose-600">
                                    Lepas
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    @if($known->isNotEmpty())
        <div class="card-elev p-6 mt-4">
            <h2 class="font-bold text-slate-900">Pernah dipakai di perangkat ini</h2>
            <p class="text-xs text-slate-500 mt-0.5">Butuh kata sandi lagi karena belum diverifikasi di sesi ini.</p>

            <div class="mt-4 space-y-2">
                @foreach($known as $account)
                    <div class="flex items-center gap-3 p-3 rounded-xl border border-slate-200">
                        <div class="w-9 h-9 rounded-full bg-slate-100 text-slate-600 flex items-center justify-center font-bold text-sm shrink-0">
                            {{ mb_strtoupper(mb_substr($account->name, 0, 1)) }}
                        </div>
                        <div class="min-w-0">
                            <p class="font-medium text-slate-900 text-sm truncate">{{ $account->name }}</p>
                            <p class="text-xs text-slate-500 truncate">{{ $account->role->label() }}</p>
                        </div>
                        <div class="ml-auto flex items-center gap-1 shrink-0">
                            <a href="{{ route('account.add', ['email' => $account->email]) }}"
                               class="text-sm font-semibold text-[color:var(--brand)] px-3 py-1.5 rounded-lg hover:bg-blue-50">
                                Masuk
                            </a>
                            <form method="post" action="{{ route('account.forget', $account->id) }}">
                                @csrf
                                <button title="Lupakan akun ini di perangkat ini"
                                        class="text-sm text-slate-400 px-2 py-1.5 rounded-lg hover:bg-rose-50 hover:text-rose-600">
                                    Lupakan
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <div class="mt-4 p-4 text-xs text-slate-500 space-y-2">
        <div class="flex items-start gap-2">
            <span>🔒</span>
            <span>Setiap akun tetap wajib melewati <strong>verifikasi 2FA sendiri</strong> saat pertama ditambahkan ke sesi ini.</span>
        </div>
        <div class="flex items-start gap-2">
            <span>🖥️</span>
            <span>Di komputer bersama (mis. loket), tekan <strong>Lepas</strong> setelah selesai agar akun tidak tersimpan di perangkat.</span>
        </div>
    </div>

</section>

@endsection
