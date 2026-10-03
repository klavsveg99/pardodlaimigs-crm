{{-- 1. soļa brīdinājums: īpašumam jau ir nosūtīts pieprasījums juristam.
     Nosūtīšanu var atkārtot apzināti - tāpēc tikai paziņojums, nevis bloķēšana. --}}
<div class="flex items-center gap-3 rounded-xl border border-success-200 bg-success-50 p-4 dark:border-success-700/40 dark:bg-success-900/20">
    <x-filament::icon
        icon="heroicon-o-check-circle"
        class="h-6 w-6 shrink-0 text-success-600 dark:text-success-400"
    />
    <div class="min-w-0">
        <p class="text-sm font-semibold text-success-800 dark:text-success-200">Nosūtīts juristam</p>
        @if ($sentAt)
            <p class="mt-0.5 text-xs text-success-700 dark:text-success-300">{{ $sentAt }}</p>
        @endif
    </div>
</div>
