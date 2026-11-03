<div class="pdc-users-role-filter">
    <label for="pdc-users-role-filter">Lietotāja tips</label>
    <select id="pdc-users-role-filter" wire:model.live="roleFilter">
        <option value="">Visi</option>
        @foreach (\App\Models\User::ROLE_LABELS as $value => $label)
            <option value="{{ $value }}">{{ $label }}</option>
        @endforeach
    </select>
</div>

<style>
    .pdc-users-role-filter {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.5rem;
        padding: 0.75rem 1rem 0;
    }
    .pdc-users-role-filter label {
        font-size: 0.8rem;
        font-weight: 600;
        color: #4b5563;
        white-space: nowrap;
    }
    .pdc-users-role-filter select {
        min-width: 10rem;
        padding: 0.35rem 0.6rem;
        border: 1px solid #d1d5db;
        border-radius: 0.5rem;
        background: #ffffff;
        font-size: 0.85rem;
        color: #111827;
    }
    html.dark .pdc-users-role-filter label {
        color: #9ca3af;
    }
    html.dark .pdc-users-role-filter select {
        background: #111827;
        border-color: #374151;
        color: #f3f4f6;
    }
    @media (max-width: 639px) {
        .pdc-users-role-filter {
            align-items: stretch;
            flex-direction: column;
        }
        .pdc-users-role-filter select {
            width: 100%;
        }
    }
</style>
