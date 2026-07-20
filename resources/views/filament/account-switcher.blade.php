{{--
    Pemilih akun ala Instagram/WhatsApp, disisipkan di topbar panel lewat render
    hook USER_MENU_BEFORE (dipilih karena hook di dalam dropdown avatar hanya
    ikut ter-render bila panel mengaktifkan halaman profil — panel ini tidak).

    Hanya menampilkan; semua keputusan boleh/tidaknya pindah ada di
    AccountSwitcher + AccountSwitchController.
--}}
@php
    use App\Services\AccountSwitcher;

    $switcher = app(AccountSwitcher::class);
    $current = filament()->auth()->user();
    $switchable = $switcher->isLinkable($current) ? $switcher->switchable() : collect();
    $known = $switcher->isLinkable($current) ? $switcher->known() : collect();
@endphp

@if ($switcher->isLinkable($current))
    <x-filament::dropdown placement="bottom-end" teleport width="xs">
        <x-slot name="trigger">
            <button
                type="button"
                title="Ganti akun petugas"
                class="fi-btn fi-btn-size-sm fi-color-gray fi-btn-color-gray flex items-center gap-x-2 rounded-lg px-2 py-1.5 text-sm font-medium text-gray-700 outline-none transition duration-75 hover:bg-gray-50 focus-visible:bg-gray-50 dark:text-gray-200 dark:hover:bg-white/5 dark:focus-visible:bg-white/5"
            >
                <span class="hidden max-w-40 truncate sm:block">{{ $current->name }}</span>
                @if ($switchable->isNotEmpty())
                    <span class="fi-badge fi-color-primary rounded-md bg-primary-50 px-1.5 py-0.5 text-xs font-medium text-primary-600 dark:bg-primary-400/10 dark:text-primary-400">
                        +{{ $switchable->count() }}
                    </span>
                @endif
                <x-filament::icon
                    icon="heroicon-m-chevron-up-down"
                    class="h-4 w-4 shrink-0 text-gray-400 dark:text-gray-500"
                />
            </button>
        </x-slot>

        <x-filament::dropdown.header icon="heroicon-m-user-circle">
            {{ $current->name }} — {{ $current->role->label() }}
        </x-filament::dropdown.header>

        @if ($switchable->isNotEmpty())
            <x-filament::dropdown.list>
                @foreach ($switchable as $account)
                    {{-- tag="form" → komponen membungkus tombol dalam <form> + @csrf.
                         Id akun ikut di URL, jadi tidak perlu input tersembunyi
                         (input di dalam <button> tidak akan terkirim). --}}
                    <x-filament::dropdown.list.item
                        tag="form"
                        method="post"
                        :action="route('account.switch', $account->id)"
                        :image="filament()->getUserAvatarUrl($account)"
                    >
                        <span class="block truncate">{{ $account->name }}</span>
                        <span class="block truncate text-xs text-gray-500 dark:text-gray-400">
                            {{ $account->role->label() }}
                        </span>
                    </x-filament::dropdown.list.item>
                @endforeach
            </x-filament::dropdown.list>
        @endif

        @if ($known->isNotEmpty())
            {{-- Pernah dipakai di perangkat ini tapi belum diverifikasi di sesi
                 ini → tetap lewat form password, bukan pindah instan. --}}
            <x-filament::dropdown.list>
                @foreach ($known as $account)
                    <x-filament::dropdown.list.item
                        tag="a"
                        :href="route('account.add', ['email' => $account->email])"
                        icon="heroicon-m-lock-closed"
                        color="gray"
                    >
                        <span class="block truncate">{{ $account->name }}</span>
                        <span class="block truncate text-xs text-gray-500 dark:text-gray-400">
                            Perlu kata sandi
                        </span>
                    </x-filament::dropdown.list.item>
                @endforeach
            </x-filament::dropdown.list>
        @endif

        <x-filament::dropdown.list>
            <x-filament::dropdown.list.item
                tag="a"
                :href="route('account.add')"
                icon="heroicon-m-plus-circle"
            >
                Tambah akun
            </x-filament::dropdown.list.item>
        </x-filament::dropdown.list>
    </x-filament::dropdown>
@endif
