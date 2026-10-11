<x-layouts::app :title="__('Tambah Modul Pembelajaran')">
    <div class="flex w-full flex-1 flex-col gap-6">
        <div>
            <flux:heading size="xl">{{ __('Tambah Modul Pembelajaran') }}</flux:heading>
            <flux:subheading>{{ __('Modul baharu akan ditambah pada susunan topik seterusnya.') }}</flux:subheading>
        </div>

        <form method="POST" action="{{ route('teacher.topics.store') }}" enctype="multipart/form-data" class="w-full max-w-3xl space-y-5">
            @csrf

            <div>
                <label for="topic_name" class="mb-1 block text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ __('Nama Modul') }}</label>
                <input id="topic_name" name="topic_name" type="text" value="{{ old('topic_name') }}" required class="w-full border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 outline-none focus:border-zinc-700 dark:border-zinc-600 dark:bg-zinc-950 dark:text-zinc-100" />
                @error('topic_name')
                    <p class="mt-1 text-sm text-red-700 dark:text-red-300">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="content_text" class="mb-1 block text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ __('Kandungan Pembelajaran') }}</label>
                <textarea id="content_text" name="content_text" rows="12" class="w-full border border-zinc-300 bg-white px-3 py-2 text-sm leading-6 text-zinc-900 outline-none focus:border-zinc-700 dark:border-zinc-600 dark:bg-zinc-950 dark:text-zinc-100">{{ old('content_text') }}</textarea>
                @error('content_text')
                    <p class="mt-1 text-sm text-red-700 dark:text-red-300">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="pdf_file" class="mb-1 block text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ __('Fail PDF (maksimum 50 MB)') }}</label>
                <input id="pdf_file" name="pdf_file" type="file" accept=".pdf,application/pdf" class="block w-full border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 file:me-3 file:border-0 file:bg-zinc-100 file:px-3 file:py-1.5 file:text-sm file:font-medium dark:border-zinc-600 dark:bg-zinc-950 dark:text-zinc-100 dark:file:bg-zinc-800" />
                @error('pdf_file')
                    <p class="mt-1 text-sm text-red-700 dark:text-red-300">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="youtube_url" class="mb-1 block text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ __('Pautan YouTube (Pilihan)') }}</label>
                <input id="youtube_url" name="youtube_url" type="url" maxlength="500" value="{{ old('youtube_url') }}" placeholder="https://www.youtube.com/watch?v=..." class="w-full border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 dark:border-zinc-600 dark:bg-zinc-950 dark:text-zinc-100" />
                <p class="mt-1 text-xs text-zinc-600 dark:text-zinc-300">{{ __('Satu pautan video bagi setiap modul. Kosongkan untuk membuang pautan.') }}</p>
                @error('youtube_url')
                    <p class="mt-1 text-sm text-red-700 dark:text-red-300">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="status" class="mb-1 block text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ __('Status') }}</label>
                <select id="status" name="status" class="w-full border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 outline-none focus:border-zinc-700 dark:border-zinc-600 dark:bg-zinc-950 dark:text-zinc-100">
                    <option value="draft" @selected(old('status', 'draft') === 'draft')>{{ __('Draf') }}</option>
                    <option value="published" @selected(old('status') === 'published')>{{ __('Diterbitkan') }}</option>
                    <option value="archived" @selected(old('status') === 'archived')>{{ __('Diarkibkan') }}</option>
                </select>
                @error('status')
                    <p class="mt-1 text-sm text-red-700 dark:text-red-300">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex flex-wrap gap-3">
                <button type="submit" class="border border-zinc-900 bg-zinc-900 px-4 py-2 text-sm font-medium text-white dark:border-zinc-100 dark:bg-zinc-100 dark:text-zinc-900">{{ __('Tambah Modul') }}</button>
                <a href="{{ route('teacher.topics.index') }}" class="border border-zinc-400 px-4 py-2 text-sm font-medium text-zinc-900 dark:border-zinc-600 dark:text-zinc-100" wire:navigate>{{ __('Kembali ke Modul Pembelajaran') }}</a>
            </div>
        </form>
    </div>
</x-layouts::app>
