<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body @class(['min-h-screen bg-white dark:bg-zinc-800', 'spark-admin' => auth()->user()->isStaff()])>
        <flux:sidebar sticky collapsible="mobile" class="admin-sidebar dark border-e border-zinc-700 bg-zinc-900">
            <flux:sidebar.header>
                <a href="{{ route('dashboard') }}" class="admin-brand" wire:navigate aria-label="{{ __('SPARK - Papan Pemuka') }}">
                    <span class="admin-brand-mark"><flux:icon.bolt variant="solid" class="size-6" /></span>
                    <span><strong>SPARK</strong><small>{{ auth()->user()->isAdmin() ? __('Pentadbir') : __('Guru') }}</small></span>
                </a>
                <flux:sidebar.toggle class="lg:hidden" icon="x-mark" aria-label="{{ __('Tutup navigasi') }}" />
            </flux:sidebar.header>

            <flux:sidebar.nav>
                <flux:sidebar.group :heading="__('Pemantauan')" class="grid">
                    <flux:sidebar.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard', 'teacher.dashboard', 'student.dashboard')" wire:navigate>
                        {{ __('Papan Pemuka') }}
                    </flux:sidebar.item>

                    @if (auth()->user()->isStaff())
                        <flux:sidebar.item icon="academic-cap" :href="route('teacher.progress.index')" :current="request()->routeIs('teacher.progress.*')" wire:navigate>
                            {{ __('Pantau Kemajuan') }}
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="chart-bar" :href="route('teacher.analytics.index')" :current="request()->routeIs('teacher.analytics.*')" wire:navigate>
                            {{ __('Analitik Kelas') }}
                        </flux:sidebar.item>
                    @endif
                </flux:sidebar.group>
                @if (auth()->user()->isStaff())
                    <flux:sidebar.group :heading="__('Pengurusan')" class="mt-5 grid">
                        <flux:sidebar.item icon="book-open" :href="route('teacher.topics.index')" :current="request()->routeIs('teacher.topics.*', 'teacher.questions.*')" wire:navigate>
                            {{ __('Urus Modul Pembelajaran') }}
                        </flux:sidebar.item>
                        @if (auth()->user()->isAdmin())
                            <flux:sidebar.item icon="users" :href="route('teacher.users.index')" :current="request()->routeIs('teacher.users.*')" wire:navigate>
                                {{ __('Urus Pengguna') }}
                            </flux:sidebar.item>
                            <flux:sidebar.item icon="user-group" :href="route('teacher.kelas.index')" :current="request()->routeIs('teacher.kelas.*')" wire:navigate>
                                {{ __('Urus Kelas') }}
                            </flux:sidebar.item>
                        @endif
                    </flux:sidebar.group>
                @endif
                <flux:sidebar.group :heading="__('Sistem')" class="mt-5 grid">
                    @if (auth()->user()->isAdmin())
                        <flux:sidebar.item icon="calendar-days" :href="route('teacher.academic-years.index')" :current="request()->routeIs('teacher.academic-years.*')" wire:navigate>
                            {{ __('Tahun Akademik') }}
                        </flux:sidebar.item>
                    @endif
                    <flux:sidebar.item icon="cog-6-tooth" :href="route('profile.edit')" :current="request()->routeIs('profile.edit', 'security.edit', 'appearance.edit')" wire:navigate>
                        {{ __('Tetapan') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>
            </flux:sidebar.nav>

            <flux:spacer />

            <x-desktop-user-menu class="hidden lg:block" :name="auth()->user()->full_name" />
        </flux:sidebar>

        <flux:header class="admin-topbar">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" aria-label="{{ __('Buka navigasi') }}" />
            <nav aria-label="{{ __('Jejak navigasi') }}" class="admin-breadcrumb">
                <a href="{{ route('dashboard') }}" wire:navigate>SPARK</a>
                <flux:icon.chevron-right class="size-3 shrink-0" />
                <span aria-current="page">{{ $title ?? __('Papan Pemuka') }}</span>
            </nav>
            <flux:spacer />
            <span class="admin-role hidden sm:inline-flex">{{ auth()->user()->isAdmin() ? __('Pentadbir') : __('Guru') }}</span>

            <flux:dropdown position="top" align="end">
                <flux:profile
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevron-down"
                    aria-label="{{ __('Menu akaun') }}"
                />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <flux:avatar
                                    :name="auth()->user()->full_name"
                                    :initials="auth()->user()->initials()"
                                />

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <flux:heading class="truncate">{{ auth()->user()->full_name }}</flux:heading>
                                    <flux:text class="truncate">{{ auth()->user()->username }}</flux:text>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                            {{ __('Profil') }}
                        </flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item
                            as="button"
                            type="submit"
                            icon="arrow-right-start-on-rectangle"
                            class="w-full cursor-pointer"
                            data-test="logout-button"
                        >
                            {{ __('Log Keluar') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        {{ $slot }}

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
