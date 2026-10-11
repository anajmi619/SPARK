@props(['url', 'downloadUrl', 'title'])

<section {{ $attributes->merge(['class' => 'w-full min-w-0 overflow-hidden rounded border border-zinc-300 dark:border-zinc-700']) }} aria-label="{{ __('Dokumen PDF: :title', ['title' => $title]) }}">
    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-zinc-300 bg-zinc-100 px-4 py-3 dark:border-zinc-700 dark:bg-zinc-800">
        <div class="flex min-w-0 items-center gap-2">
            <flux:icon.document-text class="size-5 shrink-0 text-zinc-600 dark:text-zinc-300" />
            <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Nota Pembelajaran (PDF)') }}</h2>
        </div>
        <div class="flex flex-wrap gap-2">
            <flux:button size="sm" :href="$url" target="_blank" rel="noopener noreferrer" icon="arrow-top-right-on-square">{{ __('Buka Tab Baharu') }}</flux:button>
            <flux:button size="sm" :href="$downloadUrl" icon="arrow-down-tray">{{ __('Muat Turun') }}</flux:button>
        </div>
    </div>
    <object data="{{ $url }}" type="application/pdf" aria-label="{{ __('Pratonton PDF: :title', ['title' => $title]) }}" class="block h-[70vh] min-h-96 w-full bg-zinc-200 dark:bg-zinc-900">
        <div class="flex h-full min-h-96 flex-col items-center justify-center gap-4 px-6 py-10 text-center">
            <p class="text-sm text-zinc-700 dark:text-zinc-200">{{ __('Pratonton PDF tidak tersedia dalam pelayar ini.') }}</p>
            <flux:button :href="$url" target="_blank" rel="noopener noreferrer" icon="document-text">{{ __('Buka PDF') }}</flux:button>
        </div>
    </object>
</section>
