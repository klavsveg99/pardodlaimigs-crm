<?php

declare(strict_types=1);

namespace App\Filament\Forms\Components;

use Filament\Forms\Components\Field;

class AttachmentsGrid extends Field
{
    protected string $view = 'filament.forms.components.attachments-grid';

    public bool $reorderable = true;

    public bool $deletable = true;

    public bool $multiselect = true;

    public bool $sendable = false;

    public bool $propertySendable = false;

    public static function make(?string $name = null): static
    {
        return parent::make($name);
    }

    public function reorderable(bool $reorderable = true): static
    {
        $this->reorderable = $reorderable;

        return $this;
    }

    public function deletable(bool $deletable = true): static
    {
        $this->deletable = $deletable;

        return $this;
    }

    public function multiselect(bool $multiselect = true): static
    {
        $this->multiselect = $multiselect;

        return $this;
    }

    public function sendable(bool $sendable = true): static
    {
        $this->sendable = $sendable;

        return $this;
    }

    /**
     * Property attachments: per-file "Nosūtīt" by email to one of the
     * property's associated clients.
     */
    public function propertySendable(bool $propertySendable = true): static
    {
        $this->propertySendable = $propertySendable;

        return $this;
    }

    public function isPropertySendable(): bool
    {
        return $this->propertySendable;
    }

    public function isReorderable(): bool
    {
        return $this->reorderable;
    }

    public function isDeletable(): bool
    {
        return $this->deletable;
    }

    public function isMultiselect(): bool
    {
        return $this->multiselect;
    }

    public function isSendable(): bool
    {
        return $this->sendable;
    }
}
