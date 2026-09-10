@php
    /** @var \App\Models\Izpilditajs|null $record */
    $record = $getRecord();
    $tasks = $record
        ? $record->tasks()->latest('due_at')->limit(20)->get()
        : collect();
@endphp

@if (! $record)
    <div class="text-sm text-gray-500">Saglabā izpildītāju, lai redzētu saistītos uzdevumus.</div>
@elseif ($tasks->isEmpty())
    <div class="text-sm text-gray-500">Nav neviena uzdevuma, kas piesaistīts šim izpildītājam.</div>
@else
    <ul class="divide-y divide-gray-200 dark:divide-white/10">
        @foreach ($tasks as $task)
            <li class="flex items-center justify-between gap-3 py-2">
                <div class="min-w-0 flex-1">
                    <a href="{{ url('/tasks/'.$task->id.'/edit') }}"
                       class="font-medium text-primary-600 hover:underline truncate block">
                        {{ $task->title }}
                    </a>
                    <div class="text-xs text-gray-500">
                        @if ($task->due_at)
                            Līdz: {{ $task->due_at->format('d.m.Y H:i') }}
                        @else
                            Bez termiņa
                        @endif
                        @if ($task->completed_at)
                            <span class="ml-2 inline-flex items-center rounded-md bg-green-50 px-2 py-0.5 text-xs font-medium text-green-700 ring-1 ring-green-600/20">Pabeigts</span>
                        @elseif ($task->isOverdue())
                            <span class="ml-2 inline-flex items-center rounded-md bg-red-50 px-2 py-0.5 text-xs font-medium text-red-700 ring-1 ring-red-600/20">Nokavēts</span>
                        @endif
                    </div>
                </div>
            </li>
        @endforeach
    </ul>
@endif