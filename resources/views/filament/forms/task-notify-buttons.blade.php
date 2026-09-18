@php
    // Manual task-notification buttons. Each posts to its own endpoint and
    // the page reloads with a flash result — task emails are never sent
    // automatically on save.
    $agentName = $agentName ?? null;
    $agentEmail = $agentEmail ?? null;
    $izpilditajsName = $izpilditajsName ?? null;
    $izpilditajsEmail = $izpilditajsEmail ?? null;
    $result = session('task_notify_result');
@endphp

<div style="display: flex; flex-direction: column; gap: 0.75rem;">
    @if($result)
        <div style="background: {{ $result['ok'] ? '#f0fdf4' : '#fef2f2' }}; border: 1px solid {{ $result['ok'] ? '#86efac' : '#fca5a5' }}; color: {{ $result['ok'] ? '#166534' : '#b91c1c' }}; padding: 0.5rem 0.7rem; border-radius: 0.45rem; font-size: 0.8rem;">
            {{ $result['message'] }}
        </div>
    @endif

    <div style="display: flex; flex-wrap: wrap; gap: 1.25rem;">
        {{-- Aģents --}}
        <div style="display: flex; align-items: center; gap: 0.6rem; flex-wrap: wrap;">
            <div style="min-width: 0;">
                <div style="font-size: 0.8rem; font-weight: 600; color: #374151;">Aģents</div>
                <div style="font-size: 0.75rem; color: #6b7280;">
                    @if($agentName)
                        {{ $agentName }}@if($agentEmail) · {{ $agentEmail }}@endif
                    @else
                        Nav piešķirts
                    @endif
                </div>
            </div>
            @if($agentName && $agentEmail && $sendUrlAgent)
                <form method="POST" action="{{ $sendUrlAgent }}" style="margin: 0;">
                    @csrf
                    <button type="submit"
                        style="display: inline-flex; align-items: center; gap: 0.35rem; height: 2.1rem; padding: 0 0.8rem; border-radius: 0.5rem; background: var(--pdc-primary); color: #fff; font-size: 0.8rem; font-weight: 600; border: 1px solid var(--pdc-primary-darker, var(--pdc-primary)); cursor: pointer; white-space: nowrap;">
                        <svg style="width: 0.9rem; height: 0.9rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5"/></svg>
                        Nosūtīt
                    </button>
                </form>
            @endif
        </div>

        {{-- Izpildītājs --}}
        <div style="display: flex; align-items: center; gap: 0.6rem; flex-wrap: wrap;">
            <div style="min-width: 0;">
                <div style="font-size: 0.8rem; font-weight: 600; color: #374151;">Izpildītājs</div>
                <div style="font-size: 0.75rem; color: #6b7280;">
                    @if($izpilditajsName)
                        {{ $izpilditajsName }}@if($izpilditajsEmail) · {{ $izpilditajsEmail }}@endif
                    @else
                        Nav piesaistīts
                    @endif
                </div>
            </div>
            @if($izpilditajsName && $izpilditajsEmail && $sendUrlIzpilditajs)
                <form method="POST" action="{{ $sendUrlIzpilditajs }}" style="margin: 0;">
                    @csrf
                    <button type="submit"
                        style="display: inline-flex; align-items: center; gap: 0.35rem; height: 2.1rem; padding: 0 0.8rem; border-radius: 0.5rem; background: var(--pdc-primary); color: #fff; font-size: 0.8rem; font-weight: 600; border: 1px solid var(--pdc-primary-darker, var(--pdc-primary)); cursor: pointer; white-space: nowrap;">
                        <svg style="width: 0.9rem; height: 0.9rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5"/></svg>
                        Nosūtīt
                    </button>
                </form>
            @endif
        </div>
    </div>
</div>
