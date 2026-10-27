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

    /**
     * Kad lauks atrodas šaurā blakus kolonnā (piem. klienta lapā), ar jau
     * pievienotiem failiem "Pievienot failus" poga tiek līdzināta pa labi,
     * lai piespiestos kolonnas labajai malai. Citādi paliek pa kreisi.
     */
    public bool $addButtonAlignRight = false;

    /**
     * Rindu saraksts arī tad, ja laukam nav sūtīšanas darbību (piem. zināšanu
     * bāzes faili). Bez tā šādi lauki renderējas kā kartīšu režģis, kas nav
     * sapārots ar klienta/īpašuma pielikumu rindām.
     */
    public bool $listMode = false;

    public bool $sendable = false;

    public bool $propertySendable = false;

    public bool $recordSendable = false;

    /** Property gallery: show a "Lejupielādēt visus" ZIP button. */
    public bool $downloadable = false;

    /**
     * Foto galerija (īpašuma bildes). Tikai tai ir atšķirīgs tukšais
     * stāvoklis ("Galerijas foto nav"); pārējie pielikumu lauki lieto
     * "Pielikumu nav", arī ja kolekcija ir "gallery".
     */
    public bool $gallery = false;

    public string $collection = 'gallery';

    public static function make(?string $name = null): static
    {
        return parent::make($name);
    }

    /**
     * Filament render the custom view through the standard field wrapper so
     * the label and helper text above attachments are not lost.
     */
    public function toHtml(): string
    {
        return $this->wrapEmbeddedHtml(
            $this->renderView($this->getView())->render(),
            labelTag: 'div',
        );
    }

    public function collection(string $collection): static
    {
        $this->collection = $collection;

        return $this;
    }

    public function getCollection(): string
    {
        return $this->collection;
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

    /**
     * Records that belong to a client (viewings, tasks): per-file
     * "Nosūtīt" with the associated client as recipient.
     */
    public function recordSendable(bool $recordSendable = true): static
    {
        $this->recordSendable = $recordSendable;

        return $this;
    }

    public function isRecordSendable(): bool
    {
        return $this->recordSendable;
    }

    public function downloadable(bool $downloadable = true): static
    {
        $this->downloadable = $downloadable;

        return $this;
    }

    public function isDownloadable(): bool
    {
        return $this->downloadable;
    }

    public function gallery(bool $gallery = true): static
    {
        $this->gallery = $gallery;

        return $this;
    }

    public function isGallery(): bool
    {
        return $this->gallery;
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

    public function addButtonAlignRight(bool $addButtonAlignRight = true): static
    {
        $this->addButtonAlignRight = $addButtonAlignRight;

        return $this;
    }

    public function listMode(bool $listMode = true): static
    {
        $this->listMode = $listMode;

        return $this;
    }

    public function isListMode(): bool
    {
        return $this->listMode;
    }

    public function isAddButtonAlignRight(): bool
    {
        return $this->addButtonAlignRight;
    }

    public function isSendable(): bool
    {
        return $this->sendable;
    }
}
