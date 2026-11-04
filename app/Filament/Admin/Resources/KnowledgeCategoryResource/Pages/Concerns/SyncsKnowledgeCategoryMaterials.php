<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\KnowledgeCategoryResource\Pages\Concerns;

/**
 * Kursa (kategorijas) materiālu secības saglabāšana starpposma tabulā.
 */
trait SyncsKnowledgeCategoryMaterials
{
    /** @var array<int, array<string, mixed>> */
    protected array $categoryMaterials = [];

    /**
     * @param  array<int, array<string, mixed>>  $materials
     */
    protected function syncMaterials(array $materials): void
    {
        $sync = [];
        $order = 1;

        foreach ($materials as $row) {
            $articleId = (int) ($row['article_id'] ?? 0);

            if (! $articleId || isset($sync[$articleId])) {
                continue;
            }

            $sync[$articleId] = ['sort_order' => $order++];
        }

        $this->record->articles()->sync($sync);
    }
}
