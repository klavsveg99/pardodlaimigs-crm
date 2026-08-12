<div class="space-y-3">
    @if($clients->isNotEmpty())
        <div>
            <h4 class="text-sm font-bold text-gray-700 mb-1">Piesaistīts {{ $clients->count() }} klientiem:</h4>
            <ul class="text-sm space-y-1">
                @foreach($clients as $c)
                    <li class="flex justify-between">
                        <a href="{{ route('filament.admin.resources.clients.edit', $c->id) }}" class="text-primary-600 underline">{{ $c->name }}</a>
                        <span class="text-xs px-2 py-0.5 rounded bg-gray-100">{{ $c->relation }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    @if($deals->isNotEmpty())
        <div>
            <h4 class="text-sm font-bold text-gray-700 mb-1">Piesaistīts {{ $deals->count() }} darījumiem:</h4>
            <ul class="text-sm space-y-1">
                @foreach($deals as $d)
                    <li class="flex justify-between">
                        <span>
                            <a href="{{ route('filament.admin.resources.deals.edit', $d->id) }}" class="text-primary-600 underline">#{{ $d->id }}</a>
                            — {{ $d->client?->name }}
                        </span>
                        <span class="text-xs px-2 py-0.5 rounded bg-gray-100">{{ \App\Models\Deal::STAGES[$d->stage] ?? $d->stage }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    @if($clients->isEmpty() && $deals->isEmpty())
        <p class="text-gray-500">Šis īpašums vēl nav piesaistīts nevienam klientam vai darījumam CRM.</p>
    @endif
</div>
