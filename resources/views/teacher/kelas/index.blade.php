<x-layouts::app :title="__('Urus Kelas')">
    <div class="flex w-full flex-1 flex-col gap-6">
        <flux:heading size="xl">{{ __('Urus Kelas') }}</flux:heading>

        @if (session('status'))
            <p role="status" class="border-l-4 border-emerald-600 bg-emerald-50 p-4 text-sm text-emerald-900 dark:bg-emerald-950 dark:text-emerald-100">{{ session('status') }}</p>
        @endif
        @if (session('error'))
            <p role="alert" class="border-l-4 border-red-600 bg-red-50 p-4 text-sm text-red-800 dark:bg-red-950 dark:text-red-100">{{ session('error') }}</p>
        @endif

        <form method="POST" action="{{ route('teacher.kelas.store') }}" class="flex max-w-xl flex-wrap items-end gap-4">
            @csrf
            <div class="min-w-0 flex-1">
                <flux:input name="nama" :label="__('Nama Kelas')" :value="old('nama')" :placeholder="__('Contoh: Ibnu Sina')" maxlength="50" required />
            </div>
            <flux:button type="submit" variant="primary" icon="plus">{{ __('Tambah Kelas') }}</flux:button>
        </form>

        <div class="overflow-x-auto border border-zinc-200 dark:border-zinc-700">
            <table class="w-full text-left text-sm">
                <thead class="bg-zinc-100 dark:bg-zinc-900">
                    <tr>
                        <th class="px-4 py-3">{{ __('Nama Kelas') }}</th>
                        <th class="px-4 py-3">{{ __('Bilangan Pelajar') }}</th>
                        <th class="px-4 py-3">{{ __('Bilangan Guru') }}</th>
                        <th class="px-4 py-3">{{ __('Tindakan') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse ($classes as $kelas)
                        <tr>
                            <td class="px-4 py-4">{{ $kelas->nama }}</td>
                            <td class="px-4 py-4">{{ $kelas->students_count }}</td>
                            <td class="px-4 py-4">{{ $kelas->teachers_count }}</td>
                            <td class="px-4 py-4">
                                <form method="POST" action="{{ route('teacher.kelas.destroy', $kelas) }}">
                                    @csrf
                                    @method('DELETE')
                                    <flux:button type="submit" variant="danger" icon="trash" size="sm" :disabled="$kelas->students_count > 0 || $kelas->teachers_count > 0">{{ __('Padam Kelas') }}</flux:button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-6 text-zinc-600 dark:text-zinc-300">{{ __('Tiada kelas tersedia.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $classes->links() }}
    </div>
</x-layouts::app>
