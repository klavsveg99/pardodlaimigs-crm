<style>
    .pdc-ai-crm {
        border: 1px solid #e5e7eb;
        border-radius: 0.75rem;
        background: #f9fafb;
        padding: 1rem;
    }
    .dark .pdc-ai-crm {
        background: #0b0f14;
        border-color: #27303a;
    }
    .pdc-ai-crm-title {
        margin: 0 0 0.625rem;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #6b7280;
    }
    .dark .pdc-ai-crm-title {
        color: #9ca3af;
    }
    .pdc-ai-crm-empty {
        margin: 0;
        font-size: 0.875rem;
        color: #6b7280;
    }
    .dark .pdc-ai-crm-empty {
        color: #9ca3af;
    }
    .pdc-ai-crm-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.5rem 1rem;
    }
    @media (min-width: 640px) {
        .pdc-ai-crm-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }
    }
    .pdc-ai-crm-item {
        min-width: 0;
    }
    .pdc-ai-crm-item dt {
        font-size: 0.72rem;
        color: #6b7280;
    }
    .dark .pdc-ai-crm-item dt {
        color: #9ca3af;
    }
    .pdc-ai-crm-item dd {
        margin: 0;
        font-size: 0.85rem;
        color: #111827;
        word-break: break-word;
    }
    .dark .pdc-ai-crm-item dd {
        color: #f3f4f6;
    }
</style>
<div class="pdc-ai-crm">
    <div class="pdc-ai-crm-title">Informācija no CRM</div>
    @if (empty($rows))
        <p class="pdc-ai-crm-empty">Īpašuma dati vēl nav aizpildīti.</p>
    @else
        <dl class="pdc-ai-crm-grid">
            @foreach ($rows as $label => $value)
                <div class="pdc-ai-crm-item">
                    <dt>{{ $label }}</dt>
                    <dd title="{{ $value }}">{{ $value }}</dd>
                </div>
            @endforeach
        </dl>
    @endif
</div>
