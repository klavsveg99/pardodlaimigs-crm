<x-filament-panels::page>
    <x-filament::section>
        <dl class="grid" style="grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 0.5rem 1.5rem;">
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Iesniegts</dt>
                <dd>{{ $record->created_at?->format('d.m.Y H:i') ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Klients</dt>
                <dd>
                    @if ($record->client)
                        <a href="{{ url('/admin/clients/' . $record->client->id . '/edit') }}"
                           style="color: rgb(40 88 84); text-decoration: underline;">
                            {{ $record->client->name }}
                        </a>
                    @else
                        <span class="text-gray-400 dark:text-gray-500">Nav piesaistīts</span>
                    @endif
                </dd>
            </div>
        </dl>
    </x-filament::section>

    <x-filament::section heading="Iesniegtā informācija">
        <div style="display: flex; flex-wrap: wrap; gap: 0.75rem;">
            @forelse ($record->fields ?? [] as $field)
                <div style="flex: 1 1 calc(50% - 0.375rem); min-width: 0; box-sizing: border-box; border-radius: 0.375rem; border: 1px solid rgb(229 231 235); background-color: rgb(255 255 255); padding: 0.5rem 0.75rem;"
                     class="dark:border-white/10 dark:bg-white/5">
                    <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                        {{ $field['name'] ?? '' }}
                    </dt>
                    <dd style="margin-top: 0.125rem; text-align: left; word-break: break-word; overflow-wrap: anywhere;"
                        class="text-sm text-gray-900 dark:text-white">@php $value = $field['value'] ?? ''; @endphp@if (is_array($value)){{ implode(', ', array_map(fn ($v) => (string) $v, $value)) }}@elseif ($value === '' || $value === null)<span class="text-gray-400 dark:text-gray-500">—</span>@else{!! nl2br(trim(e($value))) !!}@endif</dd>
                </div>
            @empty
                <p class="text-sm text-gray-400">Nav datu.</p>
            @endforelse
        </div>
    </x-filament::section>
</x-filament-panels::page>