<x-filament-panels::page>
    <x-slot name="heading">
        <h2 class="fi-header-heading">{{ __('filament-pages::ai-text-generator.title') }}</h2>
    </x-slot>

    <div class="mb-4">
        <label for="propertyId" class="block text-sm font-medium text-gray-700 mb-1">Objekts</label>
        <select wire:model.live="propertyId" id="propertyId" class="fi-input fi-text-input w-full">
            <option value="">Izvēlēties objektu</option>
            @foreach ($this->getFormSchema()[0]->getOptions() as $value => $label)
                <option value="{{ $value }}" {{ $this->propertyId == $value ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <div class="flex items-center space-x-3">
        <button wire:click="generate" class="fi-btn fi-btn-primary">
            <svg class="fi-btn__icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V17a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd" /></svg>
            Ģenerēt tekstu
        </button>
    </div>

    @if ($this->generatedLv || $this->generatedEn)
        <div class="mt-6 space-y-4">
            <div>
                <h3 class="font-medium mb-2">Latviešu valoda</h3>
                <div class="bg-gray-50 p-3 rounded border border-gray-200 break-words">{{ $this->generatedLv }}</div>
                <button class="mt-2 text-sm text-primary hover:underline" onclick="navigator.clipboard.writeText(`{{ addslashes($this->generatedLv) }}`).then(() => alert('Teksts kopēts!'));">
                    Kopēt tekstu
                </button>
            </div>

            <div>
                <h3 class="font-medium mb-2">English</h3>
                <div class="bg-gray-50 p-3 rounded border border-gray-200 break-words">{{ $this->generatedEn }}</div>
                <button class="mt-2 text-sm text-primary hover:underline" onclick="navigator.clipboard.writeText(`{{ addslashes($this->generatedEn) }}`).then(() => alert('Text copied!'));">
                    Copy text
                </button>
            </div>

            <div class="mt-4">
                <h3 class="font-medium mb-2">Facebook sludinājuma ideja (ar emojīm)</h3>
                <div class="bg-gray-50 p-3 rounded border border-gray-200 break-words">{{ $this->generatedLv }}</div>
            </div>

            <div class="mt-4">
                <h3 class="font-medium mb-2">TikTok/Reels video koncepteurs</h3>
                <div class="bg-gray-50 p-3 rounded border border-gray-200">
                    <p class="mb-2">Izmantojot šīs bildes:</p>
                    <ul class="list-disc pl-5 space-y-1">
                        @if ($property = \App\Models\CrmProperty::find($this->propertyId))
                            @foreach ($property->attachments as $attachment)
                                <li>{{ $attachment->original_name }}</li>
                            @endforeach
                            @if ($property->image_urls)
                                @foreach (json_decode($property->image_urls, true) as $url)
                                    <li>{{ basename($url) }}</li>
                                @endforeach
                            @endif
                        @endif
                    </ul>
                    <p class="mt-2">Ieteikums: 15-30 sekunda video ar ritmu mūziku, attēlu pāreji, teksta overlays ar īpašuma aprakstu un kontaktinformāciju. Beigās pievienojiet kontaktus un zvana zumu.</p>
                </div>
            </div>
        </div>
    @endif
</x-filament-panels::page>