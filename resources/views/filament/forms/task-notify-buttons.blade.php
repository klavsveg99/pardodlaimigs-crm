@php
    // Manual task-notification buttons. Buttons must NOT be <form> elements:
    // this block renders inside Filament's own <form>, and browsers drop
    // nested forms (taking the POST action with them). A small JS submit
    // creates a detached form so the POST target is preserved.
    $agentName = $agentName ?? null;
    $agentEmail = $agentEmail ?? null;
    $izpilditajsName = $izpilditajsName ?? null;
    $izpilditajsEmail = $izpilditajsEmail ?? null;
    $agentNotifiedAt = $agentNotifiedAt ?? null;
    $izpilditajsNotifiedAt = $izpilditajsNotifiedAt ?? null;
    $result = session('task_notify_result');
    $csrf = csrf_token();
    // "Nosūtīts" tiek saglabāts datubāzē (agent_notified_at /
    // izpilditajs_notified_at), tāpēc tas paliek arī pēc lapas pārlādes.
    $agentSent = (bool) $agentNotifiedAt;
    $izpilditajsSent = (bool) $izpilditajsNotifiedAt;
    $failed = $result !== null && ! $result['ok'];
@endphp

<div class="pdc-task-notify">
    @if($failed)
        <div class="pdc-task-notify-result" style="background: #fef2f2; border-color: #fca5a5; color: #b91c1c;">
            {{ $result['message'] }}
        </div>
    @endif

    <div class="pdc-task-notify-grid">
        {{-- Aģents --}}
        <div class="pdc-task-notify-person">
            <div class="pdc-task-notify-label">Aģents</div>
            <div class="pdc-task-notify-who">
                @if($agentName)
                    {{ $agentName }}@if($agentEmail) · {{ $agentEmail }}@endif
                @else
                    Nav piešķirts
                @endif
            </div>
            @if($agentSent)
                <div class="pdc-task-notify-sent" title="Nosūtīts">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M4.5 12.75l6 6 9-13.5"/></svg>
                    <span>Nosūtīts</span>
                </div>
            @elseif($agentName && $agentEmail && $sendUrlAgent)
                <button type="button"
                    class="pdc-task-notify-btn"
                    data-pdc-notify-url="{{ $sendUrlAgent }}"
                    data-pdc-notify-token="{{ $csrf }}"
                    x-on:click="pdcTaskNotify($el)">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5"/></svg>
                    Nosūtīt
                </button>
            @endif
        </div>

        {{-- Izpildītājs --}}
        <div class="pdc-task-notify-person">
            <div class="pdc-task-notify-label">Izpildītājs</div>
            <div class="pdc-task-notify-who">
                @if($izpilditajsName)
                    {{ $izpilditajsName }}@if($izpilditajsEmail) · {{ $izpilditajsEmail }}@endif
                @else
                    Nav piesaistīts
                @endif
            </div>
            @if($izpilditajsSent)
                <div class="pdc-task-notify-sent" title="Nosūtīts">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M4.5 12.75l6 6 9-13.5"/></svg>
                    <span>Nosūtīts</span>
                </div>
            @elseif($izpilditajsName && $izpilditajsEmail && $sendUrlIzpilditajs)
                <button type="button"
                    class="pdc-task-notify-btn"
                    data-pdc-notify-url="{{ $sendUrlIzpilditajs }}"
                    data-pdc-notify-token="{{ $csrf }}"
                    x-on:click="pdcTaskNotify($el)">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5"/></svg>
                    Nosūtīt
                </button>
            @endif
        </div>
    </div>
</div>

<script>
    // Submit a detached form so the POST target works even though this block
    // is nested inside Filament's own <form>.
    if (! window.pdcTaskNotify) {
        window.pdcTaskNotify = function (el) {
            if (el.disabled) return;
            el.disabled = true;
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = el.dataset.pdcNotifyUrl;
            form.style.display = 'none';
            const token = document.createElement('input');
            token.type = 'hidden';
            token.name = '_token';
            token.value = el.dataset.pdcNotifyToken;
            form.appendChild(token);
            document.body.appendChild(form);
            form.submit();
        };
    }
</script>

<style>
    /* Task notification section: name above, button below; larger gap between
       recipients and comfortable mobile stacking. Scoped to this block. */
    .pdc-task-notify {
        display: flex;
        flex-direction: column;
        gap: 1rem;
        /* Filament's section-content bottom padding collapses against the
           buttons; keep clear room under the last row. */
        padding-bottom: 0.5rem;
    }

    .pdc-task-notify-result {
        border: 1px solid;
        padding: 0.5rem 0.7rem;
        border-radius: 0.45rem;
        font-size: 0.8rem;
    }

    .pdc-task-notify-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 1.5rem 2.5rem;
    }

    .pdc-task-notify-person {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
        min-width: 0;
    }

    .pdc-task-notify-label {
        font-size: 0.8rem;
        font-weight: 600;
        color: #374151;
    }

    .pdc-task-notify-who {
        font-size: 0.78rem;
        color: #6b7280;
        overflow-wrap: anywhere;
    }

    .pdc-task-notify-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        height: 2.1rem;
        padding: 0 0.9rem;
        border-radius: 0.5rem;
        background: var(--pdc-primary);
        color: #fff;
        font-size: 0.8rem;
        font-weight: 600;
        border: 1px solid var(--pdc-primary-darker, var(--pdc-primary));
        cursor: pointer;
        white-space: nowrap;
    }

    .pdc-task-notify-btn:disabled {
        opacity: 0.6;
        cursor: default;
    }

    .pdc-task-notify-sent {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        height: 2.1rem;
        padding: 0 0.9rem;
        border-radius: 0.5rem;
        background: #f0fdf4;
        color: #16a34a;
        font-size: 0.8rem;
        font-weight: 600;
        border: 1px solid #86efac;
    }

    .pdc-task-notify-sent svg {
        width: 0.9rem;
        height: 0.9rem;
        flex: none;
        color: #9ca3af;
    }

    .dark .pdc-task-notify-sent {
        background: rgba(22, 163, 74, 0.12);
        border-color: rgba(134, 239, 172, 0.35);
        color: #4ade80;
    }

    .dark .pdc-task-notify-sent svg {
        color: #71717a;
    }

    .pdc-task-notify-btn svg {
        width: 0.9rem;
        height: 0.9rem;
        flex: none;
    }

    .dark .pdc-task-notify-label {
        color: #d4d4d8;
    }

    .dark .pdc-task-notify-who {
        color: #9ca3af;
    }

    @media (max-width: 640px) {
        .pdc-task-notify-grid {
            grid-template-columns: minmax(0, 1fr);
            gap: 1.25rem;
        }
    }
</style>
