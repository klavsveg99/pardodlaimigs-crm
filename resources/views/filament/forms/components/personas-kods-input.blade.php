@php
    $statePath = $getStatePath();
@endphp

<div
    x-data="{
        format(v) {
            let r = v.replace(/[^0-9]/g, '').slice(0, 11);
            return r.length > 6 ? r.slice(0, 6) + '-' + r.slice(6) : r;
        },
        onKd(e) {
            let p = $refs.kdInput.selectionStart;
            if (e.key === 'Backspace' && p === 7 && $refs.kdInput.value[6] === '-') {
                e.preventDefault();
                let d = $refs.kdInput.value.replace(/[^0-9]/g, '').split('');
                d.splice(5, 1);
                let v = this.format(d.join(''));
                $refs.kdInput.value = v;
                $wire.set('{{ $statePath }}', v);
                $nextTick(() => { $refs.kdInput.selectionStart = $refs.kdInput.selectionEnd = 6; });
            }
            if (e.key === 'Delete' && p === 6 && $refs.kdInput.value[6] === '-') {
                e.preventDefault();
                let d = $refs.kdInput.value.replace(/[^0-9]/g, '').split('');
                d.splice(6, 1);
                let v = this.format(d.join(''));
                $refs.kdInput.value = v;
                $wire.set('{{ $statePath }}', v);
                $nextTick(() => { $refs.kdInput.selectionStart = $refs.kdInput.selectionEnd = 7; });
            }
        },
        onIn() {
            let v = this.format($refs.kdInput.value);
            $refs.kdInput.value = v;
            $wire.set('{{ $statePath }}', v);
        }
    }"
>
    <input
        type="text"
        x-ref="kdInput"
        wire:model.live="{{ $statePath }}"
        x-on:keydown="onKd($event)"
        x-on:input="onIn()"
        class="fi-input fi-text-input"
        maxlength="12"
        style="width: 100%;"
    />
</div>
