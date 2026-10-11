<x-layouts::app :title="__('Tahun Akademik dan Peralihan')">
    <div class="flex w-full min-w-0 flex-col gap-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <flux:heading size="xl">{{ __('Tahun Akademik dan Peralihan') }}</flux:heading>
            @if ($years->isNotEmpty() && $nextNumber <= 2155)
                <form method="POST" action="{{ route('teacher.academic-years.store') }}">
                    @csrf
                    <input type="hidden" name="year" value="{{ $nextNumber }}" />
                    <flux:button type="submit" icon="plus">{{ __('Tambah Tahun :year', ['year' => $nextNumber]) }}</flux:button>
                </form>
            @endif
        </div>

        @if (session('status'))
            <p role="status" class="border-l-4 border-emerald-600 bg-emerald-50 px-4 py-3 text-sm text-emerald-900 dark:bg-emerald-950 dark:text-emerald-100">{{ session('status') }}</p>
        @endif
        @if ($errors->any())
            <div role="alert" class="text-sm text-red-700 dark:text-red-300">@foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>
        @endif

        <dl class="grid gap-5 border-y border-zinc-200 py-5 sm:grid-cols-3 dark:border-zinc-700">
            <div><dt class="text-sm text-zinc-500">{{ __('Tahun Aktif') }}</dt><dd class="mt-1 text-2xl font-semibold">{{ $current?->year ?? __('Tiada') }}</dd></div>
            <div><dt class="text-sm text-zinc-500">{{ __('Tahun Akan Datang') }}</dt><dd class="mt-1 text-2xl font-semibold">{{ $next?->year ?? __('Belum ditetapkan') }}</dd></div>
            <div><dt class="text-sm text-zinc-500">{{ __('Jadual Peralihan (Waktu Malaysia)') }}</dt><dd class="mt-1 text-base font-semibold">{{ $next?->rollover_at?->setTimezone('Asia/Kuala_Lumpur')->format('d/m/Y, H:i') ?? __('Belum dijadualkan') }}</dd>
                @if ($next?->rollover_at?->isPast())<p class="mt-1 text-sm text-amber-700 dark:text-amber-300">{{ __('Menunggu pelaksanaan penjadual') }}</p>@endif
            </div>
        </dl>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[760px] text-left text-sm">
                <thead class="border-b border-zinc-300 bg-zinc-100 dark:border-zinc-700 dark:bg-zinc-900"><tr>
                    @foreach (['Tahun', 'Status', 'Pelajar Aktif', 'Diluluskan', 'Menunggu', 'Tindakan'] as $label)<th scope="col" class="px-4 py-3 font-medium">{{ __($label) }}</th>@endforeach
                </tr></thead>
                <tbody>
                    @forelse ($years as $year)
                        <tr class="border-b border-zinc-200 dark:border-zinc-700">
                            <th scope="row" class="px-4 py-4 font-semibold">{{ $year->year }}</th>
                            <td class="px-4 py-4"><flux:badge size="sm" :color="match ($year->status->value) { 'active' => 'emerald', 'upcoming' => 'amber', default => 'zinc' }">{{ match ($year->status->value) { 'active' => __('Aktif'), 'upcoming' => __('Akan Datang'), 'closed' => __('Ditutup') } }}</flux:badge></td>
                            <td class="px-4 py-4">{{ $year->active_students_count }}</td>
                            <td class="px-4 py-4">{{ $year->approved_students_count }}</td>
                            <td class="px-4 py-4">{{ $year->pending_students_count }}</td>
                            <td class="px-4 py-4">
                                @if ($next?->is($year))
                                    <flux:button size="sm" icon="eye" :href="route('teacher.academic-years.show', $year)" wire:navigate>{{ __('Pratonton Peralihan') }}</flux:button>
                                @elseif ($year->status->value !== 'upcoming')
                                    <flux:button size="sm" icon="chart-bar" :href="route('teacher.analytics.index', ['academic_year_id' => $year->academic_year_id])" wire:navigate>{{ $year->status->value === 'closed' ? __('Laporan Sejarah') : __('Analitik Kelas') }}</flux:button>
                                @else
                                    <span class="text-zinc-500">{{ __('Menunggu giliran') }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-8 text-center text-zinc-500">{{ __('Tiada tahun akademik ditetapkan.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <section class="flex min-w-0 flex-col gap-4">
            <flux:heading size="lg">{{ __('Sejarah Jadual dan Peralihan') }}</flux:heading>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[900px] text-left text-sm">
                    <thead class="border-b border-zinc-300 bg-zinc-100 dark:border-zinc-700 dark:bg-zinc-900"><tr>
                        @foreach (['Masa (Malaysia)', 'Tahun Sasaran', 'Aktiviti', 'Ditetapkan Oleh', 'Jadual (Malaysia)', 'Dinyahaktifkan / Diaktifkan'] as $label)<th scope="col" class="px-4 py-3 font-medium">{{ __($label) }}</th>@endforeach
                    </tr></thead>
                    <tbody>
                        @forelse ($audits as $audit)
                            <tr class="border-b border-zinc-200 dark:border-zinc-700">
                                <td class="px-4 py-3">{{ $audit->occurred_at->setTimezone('Asia/Kuala_Lumpur')->format('d/m/Y H:i') }}</td>
                                <td class="px-4 py-3">{{ $audit->academicYear->year }}</td>
                                <td class="px-4 py-3">{{ match ($audit->event) { 'scheduled' => __('Jadual Disimpan'), 'cancelled' => __('Jadual Dibatalkan'), 'completed' => __('Peralihan Berjaya'), 'failed' => __('Peralihan Gagal') } }}
                                    @if ($audit->event === 'failed')<p class="mt-1 text-xs text-red-700 dark:text-red-300">{{ __('Tiada perubahan separa. Akan dicuba semula.') }}</p>@endif
                                </td>
                                <td class="px-4 py-3">{{ $audit->performer?->full_name ?? __('Sistem') }}</td>
                                <td class="px-4 py-3">{{ $audit->scheduled_at?->setTimezone('Asia/Kuala_Lumpur')->format('d/m/Y H:i') ?? '-' }}</td>
                                <td class="px-4 py-3">{{ $audit->deactivated_count ?? '-' }} / {{ $audit->activated_count ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-8 text-center text-zinc-500">{{ __('Belum ada rekod peralihan.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $audits->links() }}
        </section>
    </div>
</x-layouts::app>
