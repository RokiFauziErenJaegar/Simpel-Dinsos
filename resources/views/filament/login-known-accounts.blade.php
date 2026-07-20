{{--
    Pintasan "pernah login di perangkat ini" pada halaman login panel.

    Murni pengisi kolom email — cookie hanya mengingat SIAPA yang pernah login,
    bukan bukti otentikasi. Kata sandi + 2FA tetap wajib. Lihat AccountSwitcher.
--}}
@php
    use App\Services\AccountSwitcher;

    $known = app(AccountSwitcher::class)->known();
@endphp

@if ($known->isNotEmpty())
    <div class="mt-6 border-t border-gray-200 pt-6 dark:border-white/10">
        <p class="text-xs font-medium text-gray-500 dark:text-gray-400">
            Pernah masuk di perangkat ini
        </p>

        <div class="mt-3 flex flex-col gap-y-2">
            @foreach ($known as $account)
                <button
                    type="button"
                    x-data
                    x-on:click="
                        const input = $root.closest('form')?.querySelector('input[type=email]')
                            ?? document.querySelector('input[type=email]');
                        if (! input) return;
                        input.value = @js($account->email);
                        {{-- Livewire mengikat lewat wire:model, jadi nilai yang di-set
                             langsung harus diumumkan agar state komponen ikut berubah. --}}
                        input.dispatchEvent(new Event('input', { bubbles: true }));
                        input.dispatchEvent(new Event('change', { bubbles: true }));
                        (document.querySelector('input[type=password]') ?? input).focus();
                    "
                    class="flex items-center gap-x-3 rounded-lg border border-gray-200 px-3 py-2 text-start transition hover:bg-gray-50 dark:border-white/10 dark:hover:bg-white/5"
                >
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary-500 text-sm font-semibold text-white">
                        {{ mb_strtoupper(mb_substr($account->name, 0, 1)) }}
                    </span>
                    <span class="min-w-0">
                        <span class="block truncate text-sm font-medium text-gray-950 dark:text-white">
                            {{ $account->name }}
                        </span>
                        <span class="block truncate text-xs text-gray-500 dark:text-gray-400">
                            {{ $account->role->label() }}
                        </span>
                    </span>
                </button>
            @endforeach
        </div>
    </div>
@endif
