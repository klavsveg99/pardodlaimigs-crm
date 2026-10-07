{{-- 1. soļa brīdinājums: īpašumam jau ir nosūtīts pieprasījums juristam.
     Nosūtīšanu var atkārtot apzināti - tāpēc tikai paziņojums, nevis bloķēšana.
     Parāda arī pēdējā nosūtītā e-pasta saņēmēju, tematu un saturu. --}}
@php($lastEmail = $lastEmail ?? null)
<div class="flex flex-col gap-3">
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

    @if ($lastEmail)
        <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 text-sm dark:border-[#27303a] dark:bg-[#0b0f14]">
            <p class="font-semibold text-gray-500 dark:text-gray-400">Pēdējais e-pasts</p>
            <dl class="mt-2 flex flex-col gap-1.5">
                <div class="flex gap-2">
                    <dt class="w-20 shrink-0 text-gray-500 dark:text-gray-400">Saņēmējs</dt>
                    <dd class="min-w-0 break-words text-gray-900 dark:text-white">{{ $lastEmail['to'] ?: '—' }}</dd>
                </div>
                <div class="flex gap-2">
                    <dt class="w-20 shrink-0 text-gray-500 dark:text-gray-400">Temats</dt>
                    <dd class="min-w-0 break-words text-gray-900 dark:text-white">{{ $lastEmail['subject'] ?: '—' }}</dd>
                </div>
                @if (! empty($lastEmail['content']))
                    <div class="flex gap-2">
                        <dt class="w-20 shrink-0 text-gray-500 dark:text-gray-400">Saturs</dt>
                        <dd class="max-h-40 min-w-0 overflow-y-auto whitespace-pre-wrap break-words rounded-lg border border-gray-200 bg-white p-2 text-xs text-gray-700 dark:border-[#27303a] dark:bg-[#0b0f14] dark:text-gray-300">{{ $lastEmail['content'] }}</dd>
                    </div>
                @endif
            </dl>
        </div>
    @endif
</div>
