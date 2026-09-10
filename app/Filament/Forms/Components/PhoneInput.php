<?php

declare(strict_types=1);

namespace App\Filament\Forms\Components;

use Filament\Forms\Components\TextInput;
use Filament\Support\Enums\VerticalAlignment;
use Filament\Support\View\ComponentAttributeBag;

class PhoneInput extends TextInput
{
    public function toEmbeddedHtml(): string
    {
        $statePath = $this->getStatePath();

        // View / readonly pages: no prefix selector, just the full stored
        // value (with + dial code) as static text.
        $isStatic = $this->getContainer()->getOperation() === 'view' || $this->isDisabled() || $this->isReadOnly();

        ob_start(); ?>
        <div x-data="pdcPhone('<?= e($statePath) ?>', <?= $isStatic ? 'true' : 'false' ?>)" class="pdc-phone-field<?= $isStatic ? ' pdc-phone-field-static' : '' ?>" wire:ignore>
            <input
                type="tel"
                x-ref="tel"
                wire:ignore
                autocomplete="tel"
                spellcheck="false"
                class="fi-input fi-text-input"
                <?= $isStatic ? 'readonly tabindex="-1"' : '' ?>
            />
            <p x-cloak x-show="error" style="margin-top:0.3rem;font-size:0.8125rem;line-height:1.25;color:#cf2e2e;">
                Nederīgs tālruņa numurs — pārbaudiet valsts kodu un numuru
            </p>
        </div>
        <?php
        $slotHtml = ob_get_clean();

        return $this->wrapEmbeddedHtml(
            $this->wrapInputHtml(
                $slotHtml,
                attributes: (new ComponentAttributeBag)->class(['fi-fo-text-input']),
            ),
            inlineLabelVerticalAlignment: VerticalAlignment::Center,
        );
    }
}
