<x-layouts::app :title="__('Pratonton Peralihan')">
    <div class="flex w-full min-w-0 max-w-5xl flex-col gap-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <flux:heading size="xl">{{ __('Peralihan :from ke :to', ['from' => $current?->year ?? '-', 'to' => $year->year]) }}</flux:heading>
            <flux:button icon="arrow-left" :href="route('teacher.academic-years.index')" wire:navigate>{{ __('Kembali') }}</flux:button>
        </div>

        <dl class="grid gap-5 border-y border-zinc-200 py-5 sm:grid-cols-3 dark:border-zinc-700">
            <div><dt class="text-sm text-zinc-500">{{ __('Pelajar Akan Dinyahaktifkan') }}</dt><dd class="mt-1 text-2xl font-semibold">{{ $current?->active_students_count ?? 0 }}</dd></div>
            <div><dt class="text-sm text-zinc-500">{{ __('Pelajar Diluluskan untuk Diaktifkan') }}</dt><dd class="mt-1 text-2xl font-semibold">{{ $year->approved_students_count }}</dd></div>
            <div><dt class="text-sm text-zinc-500">{{ __('Pendaftaran Masih Menunggu') }}</dt><dd class="mt-1 text-2xl font-semibold">{{ $year->pending_students_count }}</dd></div>
        </dl>
        <p class="border-l-4 border-amber-500 bg-amber-50 px-4 py-3 text-sm leading-6 text-amber-950 dark:bg-amber-950 dark:text-amber-100">
            {{ __('Pelajar tahun lama akan kehilangan akses. Hanya pelajar yang diluluskan akan diaktifkan. Semua keputusan kuiz, mata dan tahap terdahulu akan dikekalkan dalam laporan sejarah.') }}
        </p>
        <div>
            <flux:button icon="users" :href="route('teacher.users.index', ['academic_year_id' => $year->academic_year_id, 'status' => 'pending'])" wire:navigate>{{ __('Semak Pendaftaran Menunggu') }}</flux:button>
        </div>

        <section class="flex flex-col gap-4 border-t border-zinc-200 pt-6 dark:border-zinc-700">
            <flux:heading size="lg">{{ __('Jadual Peralihan') }}</flux:heading>
            <form method="POST" action="{{ route('teacher.academic-years.update', $year) }}" class="flex max-w-xl flex-col gap-5">
                @csrf
                @method('PATCH')
                <flux:input type="datetime-local" name="rollover_at" :label="__('Tarikh dan Masa (Malaysia, UTC+8)')" :value="old('rollover_at', $year->rollover_at?->setTimezone('Asia/Kuala_Lumpur')->format('Y-m-d\TH:i'))" required />
                <flux:error name="rollover_at" />
                <label class="flex items-start gap-3 text-sm leading-6">
                    <input type="checkbox" name="confirm" value="1" required class="mt-1 size-4 shrink-0" />
                    {{ __('Saya mengesahkan jadual dan perubahan akses pelajar yang dinyatakan di atas.') }}
                </label>
                <flux:error name="confirm" />
                <div><flux:button type="submit" variant="primary" icon="calendar-days" :disabled="$current === null">{{ __('Simpan Jadual') }}</flux:button></div>
            </form>
            @if ($year->rollover_at !== null)
                <form method="POST" action="{{ route('teacher.academic-years.cancel', $year) }}" onsubmit="return confirm('Batalkan jadual peralihan ini?')">
                    @csrf
                    @method('DELETE')
                    <flux:button type="submit" icon="x-mark">{{ __('Batalkan Jadual') }}</flux:button>
                </form>
            @endif
        </section>
    </div>
</x-layouts::app>
