<x-student-layout :title="__('Pendaftaran Pelajar')" guest>
    <div class="spark-auth flex flex-col gap-6">
        <x-science-friend class="mx-auto h-20 w-28" />
        <div class="text-center"><h1>Pendaftaran Pelajar</h1><p class="mt-3 text-sm leading-6 text-zinc-600">Akaun baharu akan menunggu kelulusan guru.</p></div>

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('register.store') }}" class="flex flex-col gap-6">
            @csrf
            @error('registration')
                <flux:text class="text-red-600">{{ $message }}</flux:text>
            @enderror

            <flux:input
                name="full_name"
                :label="__('Nama Penuh')"
                :value="old('full_name')"
                type="text"
                required
                autofocus
                autocomplete="name"
                :placeholder="__('Nama Penuh')"
            />

            <flux:input
                name="username"
                :label="__('Nama Pengguna')"
                :value="old('username')"
                type="text"
                required
                autocomplete="username"
                :placeholder="__('Nama Pengguna')"
            />

            <flux:select name="kelas_id" :label="__('Kelas')" required>
                <option value="">{{ __('-- Pilih Kelas --') }}</option>
                @foreach ($classes as $kelas)
                    <option value="{{ $kelas->id }}" @selected((string) old('kelas_id') === (string) $kelas->id)>{{ $kelas->nama }}</option>
                @endforeach
            </flux:select>

            <flux:input
                name="password"
                :label="__('Kata Laluan')"
                type="password"
                required
                autocomplete="new-password"
                :placeholder="__('Kata Laluan')"
                passwordrules="{{ \Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString() }}"
                viewable
            />

            <flux:input
                name="password_confirmation"
                :label="__('Sahkan Kata Laluan')"
                type="password"
                required
                autocomplete="new-password"
                :placeholder="__('Sahkan Kata Laluan')"
                passwordrules="{{ \Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString() }}"
                viewable
            />

            <div class="flex items-center justify-end">
                <button type="submit" class="spark-button w-full" data-test="register-user-button">
                    {{ __('Hantar Pendaftaran') }}
                </button>
            </div>
        </form>

        <div class="space-x-1 rtl:space-x-reverse text-center text-sm text-zinc-600 dark:text-zinc-400">
            <span>{{ __('Sudah mempunyai akaun?') }}</span>
            <flux:link :href="route('login')" wire:navigate>{{ __('Log Masuk') }}</flux:link>
        </div>
    </div>
</x-student-layout>
