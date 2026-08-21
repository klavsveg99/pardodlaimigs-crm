<?php

declare(strict_types=1);

namespace App\Filament\Forms\Components;

use Filament\Forms\Components\TextInput;
use Filament\Support\View\ComponentAttributeBag;

class PersonasKodsInput extends TextInput
{
    public function toEmbeddedHtml(): string
    {
        $statePath = $this->getStatePath();

        ob_start(); ?>
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
                        $wire.set('<?= e($statePath) ?>', v);
                        $nextTick(() => { $refs.kdInput.selectionStart = $refs.kdInput.selectionEnd = 6; });
                    }
                    if (e.key === 'Delete' && p === 6 && $refs.kdInput.value[6] === '-') {
                        e.preventDefault();
                        let d = $refs.kdInput.value.replace(/[^0-9]/g, '').split('');
                        d.splice(6, 1);
                        let v = this.format(d.join(''));
                        $refs.kdInput.value = v;
                        $wire.set('<?= e($statePath) ?>', v);
                        $nextTick(() => { $refs.kdInput.selectionStart = $refs.kdInput.selectionEnd = 7; });
                    }
                },
                onIn() {
                    let v = this.format($refs.kdInput.value);
                    $refs.kdInput.value = v;
                    $wire.set('<?= e($statePath) ?>', v);
                }
            }"
        >
            <input
                type="text"
                x-ref="kdInput"
                wire:model.live="<?= e($statePath) ?>"
                x-on:keydown="onKd($event)"
                x-on:input="onIn()"
                class="fi-input fi-text-input"
                maxlength="12"
                placeholder="XXXXXX-XXXXX"
            />
        </div>
        <?php
        $slotHtml = ob_get_clean();

        return $this->wrapEmbeddedHtml(
            $this->wrapInputHtml(
                $slotHtml,
                attributes: (new ComponentAttributeBag)->class(['fi-fo-text-input']),
            ),
            inlineLabelVerticalAlignment: \Filament\Support\Enums\VerticalAlignment::Center,
        );
    }
}
