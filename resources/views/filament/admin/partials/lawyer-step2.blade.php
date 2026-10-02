@php
    // "Jurista dokuments" 2. solis: atkārtoti izmanto to pašu pielikumu
    // e-pasta modāli (attachment-send-popup) ar variant = 'lawyer'.
    // Īpašumu ņemam no montētās darbības ieraksta (Livewire), jo šeit nav
    // lappuses maršruta parametra.
    $property = null;
    $page = \Livewire\Livewire::current();
    if ($page && method_exists($page, 'getMountedAction')) {
        $record = $page->getMountedAction()?->getRecord();
        $property = $record instanceof \App\Models\CrmProperty ? $record : null;
    }
    $propertySlug = (string) ($property?->slug ?? $property?->getKey() ?? request()->route('propertySlug'));

    $jurists = \App\Models\Izpilditajs::query()
        ->where('category', 'Jurists')
        ->orderBy('name')
        ->get()
        ->mapWithKeys(fn (\App\Models\Izpilditajs $j): array => [
            (string) $j->id => ['name' => (string) $j->name, 'email' => (string) $j->email],
        ])
        ->all();
@endphp

@include('filament.partials.attachment-send-popup', [
    'variant' => 'lawyer',
    'defaultTo' => '',
    'initialFiles' => [],
    'defaultSubject' => '',
    'sendUrl' => route('properties.lawyer.send-email', ['propertySlug' => $propertySlug]),
    'previewUrl' => route('properties.lawyer.preview', ['propertySlug' => $propertySlug]),
    'extraInfoName' => 'lawyer_extra_info',
    'jurists' => $jurists,
])
