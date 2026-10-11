<x-layouts::app :title="__('Tetap Semula Kata Laluan')">
    <div class="flex w-full flex-1 flex-col gap-6">
        <div>
            <flux:heading size="xl">{{ __('Tetap Semula Kata Laluan') }}</flux:heading>
            <flux:subheading>{{ __('Tetapkan kata laluan baharu untuk akaun pelajar atau guru ini.') }}</flux:subheading>
        </div>

        <div class="max-w-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
            <dl class="grid gap-3 text-sm sm:grid-cols-[150px_1fr]">
                <dt class="font-medium text-zinc-700 dark:text-zinc-300">{{ __('Nama Pengguna Sistem') }}</dt>
                <dd class="text-zinc-900 dark:text-zinc-100">{{ $student->full_name }}</dd>
                <dt class="font-medium text-zinc-700 dark:text-zinc-300">{{ __('Nama Pengguna') }}</dt>
                <dd class="text-zinc-900 dark:text-zinc-100">{{ $student->username }}</dd>
                <dt class="font-medium text-zinc-700 dark:text-zinc-300">{{ __('Peranan') }}</dt>
                <dd class="text-zinc-900 dark:text-zinc-100">{{ $student->role === \App\Enums\UserRole::Student ? __('Pelajar') : __('Guru') }}</dd>
            </dl>
        </div>

        <form method="POST" action="{{ route('teacher.users.password.update', $student) }}" class="max-w-xl space-y-5 border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
            @csrf
            @method('PUT')

            <div>
                <label for="password" class="mb-1 block text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ __('Kata Laluan Baharu') }}</label>
                <input id="password" name="password" type="password" autocomplete="new-password" required class="w-full border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 outline-none focus:border-zinc-700 dark:border-zinc-600 dark:bg-zinc-950 dark:text-zinc-100" />
                @error('password')
                    <p class="mt-1 text-sm text-red-700 dark:text-red-300">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password_confirmation" class="mb-1 block text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ __('Sahkan Kata Laluan Baharu') }}</label>
                <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required class="w-full border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 outline-none focus:border-zinc-700 dark:border-zinc-600 dark:bg-zinc-950 dark:text-zinc-100" />
            </div>

            <div class="flex flex-wrap gap-3">
                <button type="submit" class="border border-zinc-900 bg-zinc-900 px-4 py-2 text-sm font-medium text-white dark:border-zinc-100 dark:bg-zinc-100 dark:text-zinc-900">{{ __('Tetap Semula Kata Laluan') }}</button>
                <a href="{{ route('teacher.users.index') }}" class="border border-zinc-400 px-4 py-2 text-sm font-medium text-zinc-900 dark:border-zinc-600 dark:text-zinc-100" wire:navigate>{{ __('Kembali ke Urus Pengguna') }}</a>
            </div>
        </form>
    </div>
</x-layouts::app>
