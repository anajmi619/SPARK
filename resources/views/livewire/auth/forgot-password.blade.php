<x-layouts::auth :title="__('Lupa Kata Laluan')">
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('Lupa Kata Laluan')" :description="__('Masukkan nama pengguna untuk memohon bantuan pemulihan akaun')" />

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('password.recovery.store') }}" class="flex flex-col gap-6">
            @csrf

            <flux:input
                name="username"
                :label="__('Nama Pengguna')"
                type="text"
                required
                autofocus
                autocomplete="username"
                :placeholder="__('Nama Pengguna')"
            />

            <flux:button variant="primary" type="submit" class="w-full" data-test="email-password-reset-link-button">
                {{ __('Hantar Permohonan Pemulihan') }}
            </flux:button>
        </form>

        <div class="space-x-1 rtl:space-x-reverse text-center text-sm text-zinc-400">
            <span>{{ __('Kembali ke') }}</span>
            <flux:link :href="route('login')" wire:navigate>{{ __('Log Masuk') }}</flux:link>
        </div>
    </div>
</x-layouts::auth>
