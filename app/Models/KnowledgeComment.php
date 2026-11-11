<?php

declare(strict_types=1);

namespace App\Models;

use App\Filament\Admin\Resources\KnowledgeArticleResource;
use App\Filament\Admin\Resources\KnowledgeCategoryResource;
use App\Services\Mail\EmailSender;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Log;

class KnowledgeComment extends Model
{
    protected $fillable = [
        'user_id', 'parent_id', 'knowledge_article_id', 'knowledge_category_id', 'step_index', 'body',
    ];

    protected $casts = [
        'parent_id' => 'integer',
        'step_index' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->oldest();
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(KnowledgeArticle::class, 'knowledge_article_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(KnowledgeCategory::class, 'knowledge_category_id');
    }

    public function targetTitle(): string
    {
        return $this->article?->title ?? $this->category?->name ?? 'Dzēsts ieraksts';
    }

    public function targetUrl(): ?string
    {
        if ($this->article) {
            return KnowledgeArticleResource::getUrl('view', ['record' => $this->article]);
        }

        if ($this->category) {
            return KnowledgeCategoryResource::getUrl('edit', ['record' => $this->category]);
        }

        return null;
    }

    public function stepTitle(): ?string
    {
        if ($this->step_index === null) {
            return null;
        }

        $steps = $this->article?->steps;

        return is_array($steps)
            ? ($steps[$this->step_index]['title'] ?? 'Solis '.($this->step_index + 1))
            : 'Solis '.($this->step_index + 1);
    }

    /**
     * Informē administratorus par jaunu komentāru. Pats komentāra autors
     * paziņojumu nesaņem.
     */
    public function notifyAdmins(): void
    {
        try {
            $this->sendAdminNotifications();
        } catch (\Throwable $e) {
            Log::warning('Knowledge comment admin notification failed', [
                'comment' => $this->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function sendAdminNotifications(): void
    {
        $recipients = User::query()
            ->where('role', 'admin')
            ->whereNotNull('email')
            ->whereKeyNot($this->user_id)
            ->pluck('email')
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($recipients === []) {
            return;
        }

        $title = $this->targetTitle();
        $url = $this->targetUrl();

        $rows = [
            '<p><strong>'.e($this->user?->name ?? 'Lietotājs').'</strong> pievienoja komentāru: '
                .($url ? '<a href="'.e($url).'">'.e($title).'</a>' : e($title)).'</p>',
        ];

        if ($step = $this->stepTitle()) {
            $rows[] = '<p><strong>Solis:</strong> '.e($step).'</p>';
        }

        $rows[] = '<p>'.nl2br(e($this->body)).'</p>';

        foreach ($recipients as $email) {
            try {
                app(EmailSender::class)->send(
                    to: $email,
                    subject: 'Jauns komentārs: '.$title,
                    body: implode('', $rows),
                    internal: true,
                    context: ['context' => 'knowledge_comment'],
                );
            } catch (\Throwable $e) {
                Log::warning('Knowledge comment notification email failed', [
                    'comment' => $this->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
