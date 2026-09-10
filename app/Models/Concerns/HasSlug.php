<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Generates and keeps a unique `slug` derived from a title/name column.
 * Permalinks use the slug: /clients/{slug}, /users/{slug}, ...
 *
 * The slug is regenerated automatically whenever the source column
 * (name/title) changes, so permalinks always follow the record title.
 */
trait HasSlug
{
    protected static function bootHasSlug(): void
    {
        static::saving(function (Model $model): void {
            $column = $model->slugColumn();
            $source = $model->slugSourceColumn();

            if (blank($model->$source)) {
                return;
            }

            if (blank($model->$column) || $model->isDirty($source)) {
                $model->$column = static::generateUniqueSlug(
                    (string) $model->$source,
                    $column,
                    $model->getKey(),
                );
            }
        });
    }

    public function slugColumn(): string
    {
        return 'slug';
    }

    public function slugSourceColumn(): string
    {
        return 'name';
    }

    public static function generateUniqueSlug(string $title, string $column = 'slug', int|string|null $ignoreKey = null): string
    {
        $base = Str::slug($title) ?: strtolower(class_basename(static::class));
        $slug = $base;
        $i = 2;

        while (
            static::query()
                ->where($column, $slug)
                ->when(
                    $ignoreKey !== null,
                    fn ($q) => $q->whereKeyNot($ignoreKey),
                )
                ->exists()
        ) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }

    public function getRouteKeyName(): string
    {
        return $this->slugColumn();
    }

    /**
     * Vecie permalinki ar skaitlisko id (/clients/87) paliek derīgi —
     * binding meklē gan pēc slug, gan pēc id.
     */
    public function resolveRouteBindingQuery($query, $value, $field = null)
    {
        $field ??= $this->getRouteKeyName();

        if ($field === $this->slugColumn() && ctype_digit((string) $value)) {
            return $query->where(function ($q) use ($value) {
                $q->where($this->slugColumn(), $value)
                    ->orWhere($this->getQualifiedKeyName(), (int) $value);
            });
        }

        return parent::resolveRouteBindingQuery($query, $value, $field);
    }
}
