@props(['summary'])

<dl class="admin-metrics" aria-label="{{ __('Ringkasan Prestasi') }}">
    @foreach ([
        [__('Jumlah Pelajar'), $summary['studentCount'], 'users', 'blue'],
        [__('Pelajar Aktif'), $summary['activeCount'], 'user-group', 'green'],
        [__('Pelajar Memerlukan Sokongan'), $summary['supportCount'], 'academic-cap', 'amber'],
        [__('Belum Selesai Sebarang Kuiz'), $summary['noResultCount'], 'clock', 'rose'],
        [__('Purata Skor Terbaik'), $summary['averageScore'] === null ? __('Tiada Keputusan') : number_format($summary['averageScore'], 1).'%', 'chart-bar', 'blue'],
        [__('Kuiz Topik Selesai (12 Topik)'), number_format($summary['completion'], 1).'%', 'check-circle', 'green'],
    ] as [$label, $value, $icon, $tone])
        <div class="admin-metric" data-tone="{{ $tone }}">
            <dt>{{ $label }}</dt>
            <dd>{{ $value }}</dd>
            <span class="admin-metric-icon" aria-hidden="true"><flux:icon :name="$icon" class="size-5" /></span>
        </div>
    @endforeach
</dl>
