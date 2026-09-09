<div class="pdc-ai-crm-info rounded-xl border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-900/40">
    <div class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
        Informācija no CRM
    </div>
    @if (empty($rows))
        <p class="text-xs text-gray-500 dark:text-gray-400">Īpašuma dati vēl nav aizpildīti.</p>
    @else
        <dl class="grid grid-cols-2 gap-x-4 gap-y-1.5 text-xs sm:grid-cols-3">
            @foreach ($rows as $label => $value)
                <div class="min-w-0">
                    <dt class="text-gray-500 dark:text-gray-400">{{ $label }}</dt>
                    <dd class="truncate font-medium text-gray-900 dark:text-gray-100" title="{{ $value }}">{{ $value }}</dd>
                </div>
            @endforeach
        </dl>
    @endif
    <p class="mt-2 text-[11px] text-gray-400 dark:text-gray-500">Automātiski savāktie dati — atkārtoti ievadīt nevajag.</p>
</div>
