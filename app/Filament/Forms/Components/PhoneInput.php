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

        ob_start(); ?>
        <div x-data="pdcPhone('<?= e($statePath) ?>')" class="pdc-phone-field" wire:ignore>
            <input
                type="tel"
                x-ref="tel"
                wire:ignore
                autocomplete="tel"
                spellcheck="false"
                class="fi-input fi-text-input"
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
