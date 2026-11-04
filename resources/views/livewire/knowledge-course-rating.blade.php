@php
    $stars = fn (int $rating): string => str_repeat('★', $rating).str_repeat('☆', 5 - $rating);
@endphp

<section class="pdc-kb-rating">
    <style>
        .pdc-kb-rating { display: grid; gap: 0.5rem; padding: 0.7rem 0.8rem; border: 1px solid #e5e7eb; border-radius: 0.6rem; background: #f9fafb; }
        .pdc-kb-rating-head { display: flex; align-items: center; justify-content: space-between; gap: 0.75rem; flex-wrap: wrap; }
        .pdc-kb-rating-title { font-size: 0.8rem; font-weight: 700; color: #374151; margin: 0; }
        .pdc-kb-rating-value { font-size: 1rem; letter-spacing: 0.08em; color: #d97706; white-space: nowrap; }
        .pdc-kb-rating-form { display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap; }
        .pdc-kb-rating-form select {
            padding: 0.35rem 0.5rem;
            border: 1px solid #d1d5db;
            border-radius: 0.45rem;
            background: #ffffff;
            font-size: 0.82rem;
            color: #111827;
        }
        .pdc-kb-rating-error { font-size: 0.75rem; color: #dc2626; }
        html.dark .pdc-kb-rating { border-color: #27303a; background: #111820; }
        html.dark .pdc-kb-rating-title { color: #e5e7eb; }
        html.dark .pdc-kb-rating-form select { background: #0f151b; border-color: #374151; color: #f3f4f6; }
    </style>

    <div class="pdc-kb-rating-head">
        <h3 class="pdc-kb-rating-title">Kursa vērtējums</h3>
        @if ($assignment->rating)
            <span class="pdc-kb-rating-value">{{ $stars($assignment->rating) }}</span>
        @endif
    </div>

    @if (! $assignment->rating)
        <form class="pdc-kb-rating-form" wire:submit="save">
            <label for="kb-course-rating-{{ $assignment->id }}" class="pdc-kb-rating-title">Novērtē kursu</label>
            <select id="kb-course-rating-{{ $assignment->id }}" wire:model="rating" aria-label="Kursa vērtējums">
                <option value="">Izvēlies</option>
                @for ($i = 1; $i <= 5; $i++)
                    <option value="{{ $i }}">{{ $i }} - {{ $stars($i) }}</option>
                @endfor
            </select>
            <x-filament::button type="submit" size="sm" color="primary" wire:loading.attr="disabled">
                Saglabāt vērtējumu
            </x-filament::button>
            @error('rating') <span class="pdc-kb-rating-error">{{ $message }}</span> @enderror
        </form>
    @endif
</section>
