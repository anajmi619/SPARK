@props(['action', 'academicYears', 'year', 'classes', 'kelasId', 'searchable' => false])

<form method="GET" action="{{ $action }}" class="grid min-w-0 gap-4 border-y border-zinc-200 py-4 sm:grid-cols-2 xl:grid-cols-4 dark:border-zinc-700">
    <flux:select name="academic_year_id" :label="__('Tahun Akademik')">
        @forelse ($academicYears as $academicYear)
            <option value="{{ $academicYear->academic_year_id }}" @selected($year?->is($academicYear))>
                {{ $academicYear->year }}{{ $academicYear->status === \App\Enums\AcademicYearStatus::Closed ? ' (Ditutup)' : '' }}
            </option>
        @empty
            <option value="">{{ __('Tiada Tahun Akademik') }}</option>
        @endforelse
    </flux:select>
    <flux:select name="kelas_id" :label="__('Kelas')">
        <option value="">{{ __('Semua Kelas') }}</option>
        @foreach ($classes as $kelas)
            <option value="{{ $kelas->id }}" @selected($kelasId === $kelas->id)>{{ $kelas->nama }}</option>
        @endforeach
    </flux:select>
    @if ($searchable)
        <flux:input name="search" type="search" :label="__('Nama / Nama Pengguna')" :value="request('search')" maxlength="100" />
    @endif
    <div class="flex flex-wrap items-end gap-2 sm:col-span-2 {{ $searchable ? 'xl:col-span-1' : 'xl:col-span-2' }}">
        <flux:button type="submit" variant="primary" icon="funnel">{{ __('Tapis') }}</flux:button>
        <flux:button :href="$action" icon="arrow-path">{{ __('Tetap Semula') }}</flux:button>
    </div>
    @if ($searchable)
        <div class="sm:col-span-2 xl:col-span-4">
            <flux:checkbox name="support_only" value="1" :checked="request()->boolean('support_only')" :label="__('Pelajar Memerlukan Sokongan Sahaja')" />
        </div>
    @endif
    @if ($errors->any())
        <div role="alert" class="text-sm text-red-700 sm:col-span-2 xl:col-span-4 dark:text-red-300">{{ $errors->first() }}</div>
    @endif
</form>
@if ($year?->status === \App\Enums\AcademicYearStatus::Closed)
    <p class="border-l-4 border-amber-500 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:bg-amber-950 dark:text-amber-100">{{ __('Rekod Tahun :year - Baca Sahaja', ['year' => $year->year]) }}</p>
@endif
