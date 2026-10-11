<x-layouts::app :title="__('Urus Pengguna')">
    <div class="flex w-full flex-1 flex-col gap-6">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <flux:heading size="xl">{{ __('Urus Pengguna') }}</flux:heading>
                <flux:subheading>{{ __('Semak pendaftaran pelajar dan urus status akaun.') }}</flux:subheading>
            </div>
            <a href="{{ route('teacher.users.create') }}" class="inline-flex items-center justify-center border border-zinc-900 bg-zinc-900 px-4 py-2 text-sm font-medium text-white dark:border-zinc-100 dark:bg-zinc-100 dark:text-zinc-900" wire:navigate>
                {{ __('Tambah Pengguna') }}
            </a>
        </div>

        @if (session('status'))
            <div class="border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-800 dark:bg-emerald-950 dark:text-emerald-100">
                {{ session('status') }}
            </div>
        @endif

        <form method="GET" action="{{ route('teacher.users.index') }}" class="grid gap-4 border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900 md:grid-cols-2 xl:grid-cols-[minmax(0,1fr)_150px_170px_150px_auto]">
            <div>
                <label for="search" class="mb-1 block text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ __('Carian') }}</label>
                <input id="search" name="search" type="search" value="{{ request('search') }}" placeholder="{{ __('Cari nama atau nama pengguna') }}" class="w-full border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 outline-none focus:border-zinc-700 dark:border-zinc-600 dark:bg-zinc-950 dark:text-zinc-100" />
            </div>

            <div>
                <label for="academic_year_id" class="mb-1 block text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ __('Tahun Akademik') }}</label>
                <select id="academic_year_id" name="academic_year_id" class="w-full border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 outline-none focus:border-zinc-700 dark:border-zinc-600 dark:bg-zinc-950 dark:text-zinc-100">
                    <option value="">{{ __('Semua Tahun') }}</option>
                    @foreach ($academicYears as $academicYear)
                        <option value="{{ $academicYear->academic_year_id }}" @selected((string) request('academic_year_id') === (string) $academicYear->academic_year_id)>
                            {{ $academicYear->year }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <flux:select name="kelas_id" :label="__('Kelas')">
                    <option value="">{{ __('Semua Kelas') }}</option>
                    @foreach ($classes as $kelas)
                        <option value="{{ $kelas->id }}" @selected((string) request('kelas_id') === (string) $kelas->id)>{{ $kelas->nama }}</option>
                    @endforeach
                </flux:select>
            </div>
            <div>
                <label for="status" class="mb-1 block text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ __('Status') }}</label>
                <select id="status" name="status" class="w-full border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 outline-none focus:border-zinc-700 dark:border-zinc-600 dark:bg-zinc-950 dark:text-zinc-100">
                    <option value="">{{ __('Semua Status') }}</option>
                    <option value="pending" @selected(request('status') === 'pending')>{{ __('Menunggu') }}</option>
                    <option value="approved" @selected(request('status') === 'approved')>{{ __('Diluluskan') }}</option>
                    <option value="active" @selected(request('status') === 'active')>{{ __('Aktif') }}</option>
                    <option value="inactive" @selected(request('status') === 'inactive')>{{ __('Tidak Aktif') }}</option>
                    <option value="rejected" @selected(request('status') === 'rejected')>{{ __('Ditolak') }}</option>
                </select>
            </div>

            <div class="flex items-end">
                <button type="submit" class="w-full border border-zinc-900 bg-zinc-900 px-4 py-2 text-sm font-medium text-white dark:border-zinc-100 dark:bg-zinc-100 dark:text-zinc-900">
                    {{ __('Tapis') }}
                </button>
            </div>
        </form>

        <form id="review-users-form" method="POST" action="{{ route('teacher.users.review') }}">
            @csrf
        </form>

        <div class="flex flex-col gap-3 border-x border-t border-zinc-200 bg-zinc-50 px-4 py-3 dark:border-zinc-700 dark:bg-zinc-900 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ __('Pendaftaran Menunggu') }}</p>
                @error('user_ids')
                    <p class="mt-1 text-sm text-red-700 dark:text-red-300">{{ $message }}</p>
                @enderror
            </div>
            <div class="flex gap-2">
                <button form="review-users-form" type="submit" name="decision" value="approve" class="border border-zinc-900 bg-zinc-900 px-3 py-2 text-sm font-medium text-white dark:border-zinc-100 dark:bg-zinc-100 dark:text-zinc-900">
                    {{ __('Luluskan Pilihan') }}
                </button>
                <button form="review-users-form" type="submit" name="decision" value="reject" class="border border-zinc-400 bg-white px-3 py-2 text-sm font-medium text-zinc-900 dark:border-zinc-600 dark:bg-zinc-950 dark:text-zinc-100">
                    {{ __('Tolak Pilihan') }}
                </button>
            </div>
        </div>

        <div class="overflow-x-auto border border-zinc-200 dark:border-zinc-700">
            <table class="w-full min-w-[920px] divide-y divide-zinc-200 text-left text-sm dark:divide-zinc-700">
                <thead class="bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">
                    <tr>
                        <th scope="col" class="w-12 px-4 py-3 font-medium">{{ __('Pilih') }}</th>
                        <th scope="col" class="px-4 py-3 font-medium">{{ __('Nama') }}</th>
                        <th scope="col" class="px-4 py-3 font-medium">{{ __('Peranan') }}</th>
                        <th scope="col" class="px-4 py-3 font-medium">{{ __('Tahun') }}</th>
                        <th scope="col" class="px-4 py-3 font-medium">{{ __('Kelas') }}</th>
                        <th scope="col" class="px-4 py-3 font-medium">{{ __('Status') }}</th>
                        <th scope="col" class="px-4 py-3 font-medium">{{ __('Tindakan') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-950">
                    @forelse ($accounts as $account)
                        @php
                            $statusLabel = match ($account->status->value) {
                                'pending' => __('Menunggu'),
                                'approved' => __('Diluluskan'),
                                'active' => __('Aktif'),
                                'inactive' => __('Tidak Aktif'),
                                'rejected' => __('Ditolak'),
                            };
                        @endphp
                        <tr class="align-top">
                            <td class="px-4 py-4">
                                @if ($account->role === \App\Enums\UserRole::Student && $account->status === \App\Enums\UserStatus::Pending)
                                    <input form="review-users-form" type="checkbox" name="user_ids[]" value="{{ $account->user_id }}" aria-label="{{ __('Pilih :name', ['name' => $account->full_name]) }}" class="size-4 border-zinc-400 text-zinc-900 focus:ring-zinc-700" />
                                @endif
                            </td>
                            <td class="px-4 py-4">
                                <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $account->full_name }}</div>
                                <div class="mt-1 text-xs text-zinc-500">{{ $account->username }}</div>
                            </td>
                            <td class="px-4 py-4 text-zinc-700 dark:text-zinc-300">
                                {{ match ($account->role) {
                                    \App\Enums\UserRole::Student => __('Pelajar'),
                                    \App\Enums\UserRole::Teacher => __('Guru'),
                                    \App\Enums\UserRole::Admin => __('Pentadbir'),
                                    default => __('Peranan Lama'),
                                } }}
                            </td>
                            <td class="px-4 py-4 text-zinc-700 dark:text-zinc-300">{{ $account->academicYear?->year ?? '-' }}</td>
                            <td class="px-4 py-4 text-zinc-700 dark:text-zinc-300">
                                {{ $account->role === \App\Enums\UserRole::Teacher
                                    ? ($account->assignedClasses->pluck('nama')->join(', ') ?: '-')
                                    : ($account->kelas?->nama ?? '-') }}
                            </td>
                            <td class="px-4 py-4">
                                <flux:badge size="sm" :color="match ($account->status->value) { 'active', 'approved' => 'emerald', 'pending' => 'amber', 'rejected' => 'red', default => 'zinc' }">{{ $statusLabel }}</flux:badge>
                                @if ($account->recovery_requested_at !== null)
                                    <span class="mt-1 inline-flex border border-amber-300 px-2 py-1 text-xs text-amber-800 dark:border-amber-700 dark:text-amber-200">{{ __('Pemulihan diminta') }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-4">
                                @if ($account->role === \App\Enums\UserRole::Student)
                                    <div class="flex flex-wrap gap-2">
                                        @if ($account->status === \App\Enums\UserStatus::Pending)
                                            <form method="POST" action="{{ route('teacher.users.review') }}">
                                                @csrf
                                                <input type="hidden" name="user_ids[]" value="{{ $account->user_id }}" />
                                                <button type="submit" name="decision" value="approve" class="border border-zinc-900 bg-zinc-900 px-2 py-1 text-xs font-medium text-white dark:border-zinc-100 dark:bg-zinc-100 dark:text-zinc-900">{{ __('Luluskan') }}</button>
                                            </form>
                                            <form method="POST" action="{{ route('teacher.users.review') }}">
                                                @csrf
                                                <input type="hidden" name="user_ids[]" value="{{ $account->user_id }}" />
                                                <button type="submit" name="decision" value="reject" class="border border-zinc-400 px-2 py-1 text-xs font-medium text-zinc-900 dark:border-zinc-600 dark:text-zinc-100">{{ __('Tolak') }}</button>
                                            </form>
                                        @elseif ($account->status === \App\Enums\UserStatus::Active)
                                            <form method="POST" action="{{ route('teacher.users.status.update', $account) }}">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="status" value="inactive" />
                                                <button type="submit" class="border border-zinc-400 px-2 py-1 text-xs font-medium text-zinc-900 dark:border-zinc-600 dark:text-zinc-100">{{ __('Nyahaktif') }}</button>
                                            </form>
                                        @elseif ($account->status === \App\Enums\UserStatus::Inactive && $account->academicYear?->status === \App\Enums\AcademicYearStatus::Active)
                                            <form method="POST" action="{{ route('teacher.users.status.update', $account) }}">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="status" value="active" />
                                                <button type="submit" class="border border-zinc-900 bg-zinc-900 px-2 py-1 text-xs font-medium text-white dark:border-zinc-100 dark:bg-zinc-100 dark:text-zinc-900">{{ __('Aktifkan') }}</button>
                                            </form>
                                        @endif

                                        <a href="{{ route('teacher.users.password.edit', $account) }}" class="border border-zinc-400 px-2 py-1 text-xs font-medium text-zinc-900 dark:border-zinc-600 dark:text-zinc-100" wire:navigate>
                                            {{ __('Tetap Semula Kata Laluan') }}
                                        </a>
                                    </div>
                                @elseif ($account->role === \App\Enums\UserRole::Teacher)
                                    <div class="flex flex-wrap gap-2">
                                        <form method="POST" action="{{ route('teacher.users.status.update', $account) }}">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="status" value="{{ $account->status === \App\Enums\UserStatus::Active ? 'inactive' : 'active' }}" />
                                            <button type="submit" class="border border-zinc-400 px-2 py-1 text-xs font-medium text-zinc-900 dark:border-zinc-600 dark:text-zinc-100">
                                                {{ $account->status === \App\Enums\UserStatus::Active ? __('Nyahaktif') : __('Aktifkan') }}
                                            </button>
                                        </form>
                                        <a href="{{ route('teacher.users.password.edit', $account) }}" class="border border-zinc-400 px-2 py-1 text-xs font-medium text-zinc-900 dark:border-zinc-600 dark:text-zinc-100" wire:navigate>
                                            {{ __('Tetap Semula Kata Laluan') }}
                                        </a>
                                    </div>
                                @elseif ($account->role === \App\Enums\UserRole::Admin && ! $account->is(auth()->user()))
                                    <form method="POST" action="{{ route('teacher.users.status.update', $account) }}">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="status" value="{{ $account->status === \App\Enums\UserStatus::Active ? 'inactive' : 'active' }}" />
                                        <button type="submit" class="border border-zinc-400 px-2 py-1 text-xs font-medium text-zinc-900 dark:border-zinc-600 dark:text-zinc-100">
                                            {{ $account->status === \App\Enums\UserStatus::Active ? __('Nyahaktif') : __('Aktifkan') }}
                                        </button>
                                    </form>
                                @else
                                    <span class="text-xs text-zinc-500">{{ __('Akaun pentadbir semasa dilindungi') }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-sm text-zinc-500">{{ __('Tiada akaun ditemui.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($accounts->hasPages())
            <nav aria-label="{{ __('Halaman akaun') }}" class="flex items-center justify-between border-t border-zinc-200 pt-4 text-sm dark:border-zinc-700">
                @if ($accounts->onFirstPage())
                    <span class="text-zinc-400">{{ __('Sebelumnya') }}</span>
                @else
                    <a href="{{ $accounts->previousPageUrl() }}" class="font-medium text-zinc-900 underline underline-offset-4 dark:text-zinc-100">{{ __('Sebelumnya') }}</a>
                @endif

                <span class="text-zinc-600 dark:text-zinc-300">{{ __('Halaman :current daripada :last', ['current' => $accounts->currentPage(), 'last' => $accounts->lastPage()]) }}</span>

                @if ($accounts->hasMorePages())
                    <a href="{{ $accounts->nextPageUrl() }}" class="font-medium text-zinc-900 underline underline-offset-4 dark:text-zinc-100">{{ __('Seterusnya') }}</a>
                @else
                    <span class="text-zinc-400">{{ __('Seterusnya') }}</span>
                @endif
            </nav>
        @endif
    </div>
</x-layouts::app>
