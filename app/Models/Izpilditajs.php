<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasSlug;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;

class Izpilditajs extends Model
{
    use HasSlug;
    use Notifiable;

    public const CATEGORIES = [
        'Notārs' => 'Notārs',
        'Mērnieks' => 'Mērnieks',
        'Ainavu arhitekts' => 'Ainavu arhitekts',
        'Būvnieks' => 'Būvnieks',
        'Elektriķis' => 'Elektriķis',
        'Santehniķis' => 'Santehniķis',
        'Apdrošinātājs' => 'Apdrošinātājs',
        'Vērtētājs' => 'Vērtētājs',
        'Fotogrāfs' => 'Fotogrāfs',
        'Video' => 'Video',
        'Mākleris' => 'Mākleris',
        'Cits' => 'Cits',
    ];

    protected $table = 'izpilditajs';

    protected $fillable = ['name', 'category', 'email', 'phone', 'notes_md'];

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'izpilditajs_id');
    }

    public function routeNotificationForMail(): string
    {
        return (string) $this->email;
    }

    public function getDisplayLabelAttribute(): string
    {
        return "{$this->name} ({$this->category})";
    }
}
