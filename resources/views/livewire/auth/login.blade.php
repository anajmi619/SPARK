<x-student-layout :title="__('Log Masuk')" guest>
    <section class="spark-auth">
        <x-science-friend class="mx-auto mb-2 h-28 w-36" />
        <div class="mb-7 text-center">
            <h1>Selamat datang!<br />Jom belajar!</h1>
            <p class="mt-3 text-sm leading-6 text-zinc-600">Penemuan baharu menanti anda di SPARK.</p>
        </div>
        <x-auth-session-status class="mb-4 text-center" :status="session('status')" />
        @if ($errors->any())
            <div role="alert" class="mb-5 rounded-lg border border-rose-300 bg-rose-50 p-4 text-sm text-rose-900">Belum berjaya log masuk. Semak peranan, nama pengguna dan kata laluan anda. Hubungi guru jika akaun belum aktif.</div>
        @endif
        <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-5">
            @csrf
            <flux:radio.group name="role" :label="__('Peranan')" variant="segmented" required>
                <flux:radio value="student" :checked="old('role') === 'student'">Pelajar</flux:radio>
                <flux:radio value="teacher" :checked="old('role') === 'teacher'">Guru</flux:radio>
            </flux:radio.group>
            <flux:input name="username" label="Nama Pengguna" :value="old('username')" icon="user" required autocomplete="username" placeholder="Nama pengguna anda" />
            <flux:input name="password" label="Kata Laluan" type="password" icon="lock-closed" required autocomplete="current-password" placeholder="Kata laluan anda" viewable />
            <a href="{{ route('password.request') }}" class="self-end text-sm font-bold text-blue-800">Lupa Kata Laluan?</a>
            <button type="submit" class="spark-button w-full" data-test="login-button">Log Masuk <flux:icon.arrow-right class="size-5" /></button>
        </form>
        <p class="mt-7 text-center text-sm text-zinc-600">Pelajar baharu? <a href="{{ route('register') }}" class="font-extrabold text-blue-800 underline decoration-blue-300 underline-offset-4">Daftar Akaun</a></p>
    </section>
</x-student-layout>
