<x-layouts::app :title="__('Tambah Pengguna')">
    <div class="flex w-full flex-1 flex-col gap-6">
        <div>
            <flux:heading size="xl">{{ __('Tambah Pengguna') }}</flux:heading>
            <flux:subheading>{{ __('Cipta akaun pelajar atau guru melalui modul pengurusan pengguna.') }}</flux:subheading>
        </div>

        <form method="POST" action="{{ route('teacher.users.store') }}"
            x-data="{ role: @js(old('role', 'student')), syncRole() { if (this.role !== 'student') { this.$refs.year.value = ''; this.$refs.kelas.value = ''; } if (this.role !== 'teacher') { this.$refs.teacherClasses.querySelectorAll('input').forEach((input) => input.checked = false); } } }"
            x-init="syncRole()"
            class="max-w-3xl space-y-5 border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
            @csrf

            <div>
                <label for="role" class="mb-1 block text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ __('Peranan') }}</label>
                <select id="role" name="role" required @change="role = $event.target.value; syncRole()" class="w-full border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 outline-none focus:border-zinc-700 dark:border-zinc-600 dark:bg-zinc-950 dark:text-zinc-100">
                    <option value="student" @selected(old('role', 'student') === 'student')>{{ __('Pelajar') }}</option>
                    <option value="teacher" @selected(old('role') === 'teacher')>{{ __('Guru') }}</option>
                </select>
                @error('role')
                    <p class="mt-1 text-sm text-red-700 dark:text-red-300">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="full_name" class="mb-1 block text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ __('Nama Penuh') }}</label>
                <input id="full_name" name="full_name" type="text" value="{{ old('full_name') }}" required class="w-full border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 outline-none focus:border-zinc-700 dark:border-zinc-600 dark:bg-zinc-950 dark:text-zinc-100" />
                @error('full_name')
                    <p class="mt-1 text-sm text-red-700 dark:text-red-300">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="username" class="mb-1 block text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ __('Nama Pengguna') }}</label>
                <input id="username" name="username" type="text" value="{{ old('username') }}" required class="w-full border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 outline-none focus:border-zinc-700 dark:border-zinc-600 dark:bg-zinc-950 dark:text-zinc-100" />
                @error('username')
                    <p class="mt-1 text-sm text-red-700 dark:text-red-300">{{ $message }}</p>
                @enderror
            </div>

            <fieldset x-show="role === 'student'" :disabled="role !== 'student'"
                @disabled(old('role', 'student') !== 'student')
                @if (old('role', 'student') !== 'student') style="display: none" @endif
                class="grid gap-5 md:grid-cols-2">
                <div>
                    <label for="academic_year_id" class="mb-1 block text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ __('Tahun Akademik Pelajar') }}</label>
                    <select id="academic_year_id" name="academic_year_id" x-ref="year" :required="role === 'student'" class="w-full border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 outline-none focus:border-zinc-700 dark:border-zinc-600 dark:bg-zinc-950 dark:text-zinc-100">
                        <option value="">{{ __('-- Pilih Tahun Akademik --') }}</option>
                        @foreach ($academicYears as $academicYear)
                            <option value="{{ $academicYear->academic_year_id }}" @selected((string) old('academic_year_id') === (string) $academicYear->academic_year_id)>
                                {{ $academicYear->year }}
                            </option>
                        @endforeach
                    </select>
                    @error('academic_year_id')
                        <p class="mt-1 text-sm text-red-700 dark:text-red-300">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="kelas_id" class="mb-1 block text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ __('Kelas Pelajar') }}</label>
                    <select id="kelas_id" name="kelas_id" x-ref="kelas" :required="role === 'student'" class="w-full border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 outline-none focus:border-zinc-700 dark:border-zinc-600 dark:bg-zinc-950 dark:text-zinc-100">
                        <option value="">{{ __('-- Pilih Kelas --') }}</option>
                        @foreach ($classes as $kelas)
                            <option value="{{ $kelas->id }}" @selected((string) old('kelas_id') === (string) $kelas->id)>{{ $kelas->nama }}</option>
                        @endforeach
                    </select>
                    @error('kelas_id')
                        <p class="mt-1 text-sm text-red-700 dark:text-red-300">{{ $message }}</p>
                    @enderror
                </div>
            </fieldset>

            <fieldset x-show="role === 'teacher'" :disabled="role !== 'teacher'" x-ref="teacherClasses"
                @disabled(old('role', 'student') !== 'teacher')
                @if (old('role', 'student') !== 'teacher') style="display: none" @endif
                class="space-y-3 border border-zinc-200 p-4 dark:border-zinc-700">
                <legend class="px-2 text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ __('Kelas Tugasan Guru') }}</legend>
                <p class="text-sm text-zinc-600 dark:text-zinc-300">{{ __('Guru hanya boleh melihat kemajuan dan analitik bagi kelas yang dipilih.') }}</p>
                <div class="grid gap-3 sm:grid-cols-2">
                    @foreach ($classes as $kelas)
                        <label class="flex items-center gap-3 border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-700">
                            <input type="checkbox" name="kelas_ids[]" value="{{ $kelas->id }}" @checked(in_array((string) $kelas->id, array_map('strval', old('kelas_ids', [])), true)) class="size-4" />
                            <span>{{ $kelas->nama }}</span>
                        </label>
                    @endforeach
                </div>
                @error('kelas_ids')
                    <p class="text-sm text-red-700 dark:text-red-300">{{ $message }}</p>
                @enderror
                @error('kelas_ids.*')
                    <p class="text-sm text-red-700 dark:text-red-300">{{ $message }}</p>
                @enderror
            </fieldset>

            <div class="grid gap-5 md:grid-cols-2">
                <div>
                    <label for="password" class="mb-1 block text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ __('Kata Laluan') }}</label>
                    <input id="password" name="password" type="password" required autocomplete="new-password" class="w-full border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 outline-none focus:border-zinc-700 dark:border-zinc-600 dark:bg-zinc-950 dark:text-zinc-100" />
                    @error('password')
                        <p class="mt-1 text-sm text-red-700 dark:text-red-300">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password_confirmation" class="mb-1 block text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ __('Sahkan Kata Laluan') }}</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" class="w-full border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 outline-none focus:border-zinc-700 dark:border-zinc-600 dark:bg-zinc-950 dark:text-zinc-100" />
                </div>
            </div>

            <div class="border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm text-zinc-700 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-300">
                {{ __('Pelajar tahun aktif akan terus menjadi Aktif. Pelajar tahun akan datang akan menjadi Diluluskan sehingga peralihan tahun akademik. Akaun Guru akan menjadi Aktif serta-merta dan hanya boleh melihat kelas tugasannya.') }}
            </div>

            <div class="flex flex-wrap gap-3">
                <button type="submit" class="border border-zinc-900 bg-zinc-900 px-4 py-2 text-sm font-medium text-white dark:border-zinc-100 dark:bg-zinc-100 dark:text-zinc-900">{{ __('Cipta Akaun') }}</button>
                <a href="{{ route('teacher.users.index') }}" class="border border-zinc-400 px-4 py-2 text-sm font-medium text-zinc-900 dark:border-zinc-600 dark:text-zinc-100" wire:navigate>{{ __('Kembali ke Urus Pengguna') }}</a>
            </div>
        </form>
    </div>
</x-layouts::app>
