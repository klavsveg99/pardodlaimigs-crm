<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Concerns\HasSlug;
use App\Support\PhoneFormat;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

#[Fillable(['name', 'email', 'password', 'role', 'calendar_token', 'slug', 'phone', 'position', 'description', 'avatar_path', 'facebook_url', 'instagram_url', 'linkedin_url', 'website_url', 'office_address', 'email_signature', 'client_email_subject', 'client_email_body'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasSlug, Notifiable, SoftDeletes;

    /** Lomu nosaukumi formām un filtriem. */
    public const ROLE_LABELS = [
        'aģents' => 'Aģents',
        'student' => 'Students',
        'photo' => 'Fotogrāfs',
        'admin' => 'Administrators',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $user) {
            if (empty($user->calendar_token)) {
                $user->calendar_token = bin2hex(random_bytes(32));
            }
            // Ensure role defaults to aģents for new agents via UI
            if (empty($user->role)) {
                $user->role = 'aģents';
            }
        });

        // crm_properties.owner_user_id has no ON DELETE rule, so detach owned
        // properties before the row is removed; the avatar file goes too.
        static::forceDeleting(function (self $user): void {
            CrmProperty::where('owner_user_id', $user->id)->update(['owner_user_id' => null]);

            if ($user->avatar_path) {
                Storage::disk('public')->delete($user->avatar_path);
            }
        });
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }

    /**
     * "photo" loma: drīkst tikai izveidot īpašumu un rediģēt tā nosaukumu
     * un galeriju. Nekas cits nav pieejams.
     */
    public function isPhoto(): bool
    {
        return ($this->role ?? null) === 'photo';
    }

    /**
     * "student" loma redz tikai zināšanu bāzi - bez CRM sadaļām, paneļa
     * pārskata un paziņojumiem.
     */
    public function isStudent(): bool
    {
        return ($this->role ?? null) === 'student';
    }

    /**
     * Aģenta pārskats (pārdotie objekti, komisija) attiecas uz aģentiem.
     * Students un fotogrāfs to nesaņem.
     */
    public function isAgent(): bool
    {
        return in_array($this->role, [null, '', 'aģents', 'agent'], true);
    }

    public function isAdmin(): bool
    {
        return ($this->role ?? null) === 'admin';
    }

    /**
     * Lietotāja skata lapa pieejama aģentiem, studentiem un administratoriem.
     * Fotogrāfam tās nav.
     */
    public function hasUserViewPage(): bool
    {
        return $this->isAgent() || $this->isStudent() || $this->isAdmin();
    }

    /**
     * Lietotāji, kurus var izvēlēties aģentu izvēlnēs. Izslēdz "Fotogrāfs"
     * un "Students" lomas, jo tās nevar būt atbildīgie aģenti.
     */
    public function scopeAssignable(Builder $query): Builder
    {
        return $query->where(function (Builder $query): void {
            $query->whereNotIn('role', ['photo', 'student'])->orWhereNull('role');
        });
    }

    /**
     * Lietotāji, kuriem obligātais saturs ir automātiski pieejams: aģenti un
     * studenti. Administratori to pārvalda, fotogrāfiem tas nav pieejams.
     */
    public function scopeKnowledgeAudience(Builder $query): Builder
    {
        return $query->where(function (Builder $query): void {
            $query->whereNull('role')->orWhereNotIn('role', ['admin', 'photo']);
        });
    }

    public function getRoleLabelAttribute(): string
    {
        return match ($this->role) {
            'admin' => 'Administrators',
            'photo' => 'Fotogrāfs',
            'student' => 'Students',
            default => 'Aģents',
        };
    }

    public function generateCalendarToken(): string
    {
        $token = bin2hex(random_bytes(32));
        $this->update(['calendar_token' => $token]);

        return $token;
    }

    public function getCalendarFeedUrl(): string
    {
        return route('calendar.feed', [
            'user' => $this->slug ?: $this->getKey(),
            'token' => $this->calendar_token,
        ]);
    }

    public function getAvatarUrlAttribute(): ?string
    {
        if (! $this->avatar_path) {
            return null;
        }

        $disk = Storage::disk('public');
        if (! $disk->exists($this->avatar_path)) {
            return null;
        }

        return $disk->url($this->avatar_path);
    }

    /**
     * Standard e-mail signature: photo in the first column, then name, role,
     * e-mail and phone. Built as inline HTML because e-mail clients do not
     * load the CRM stylesheet.
     */
    public function buildEmailSignature(): string
    {
        $avatarUrl = null;

        if (filled($this->avatar_path) && Storage::disk('public')->exists($this->avatar_path)) {
            $base = app()->environment('production')
                ? 'https://'.config('wp-bridge.host')
                : rtrim((string) config('app.url'), '/');

            $avatarUrl = $base.'/storage/'.ltrim((string) $this->avatar_path, '/');
        }

        $phone = filled($this->phone) ? PhoneFormat::display((string) $this->phone) : null;
        $phoneHref = filled($this->phone) ? preg_replace('/[^\d+]/', '', (string) $this->phone) : null;

        $html = '<table role="presentation" cellpadding="0" cellspacing="0" border="0" '
            .'style="border-collapse:collapse;font-family:Arial,Helvetica,sans-serif;font-size:13px;'
            .'line-height:1.5;color:#374151;">'
            .'<tr>';

        if ($avatarUrl !== null) {
            $html .= '<td style="vertical-align:top;padding:0 14px 0 0;">'
                .'<img src="'.e($avatarUrl).'" alt="'.e((string) $this->name).'" width="64" height="64" '
                .'style="display:block;width:64px;height:64px;border-radius:50%;object-fit:cover;border:0;">'
                .'</td>';
        }

        $html .= '<td style="vertical-align:top;">'
            .'<div style="font-size:15px;font-weight:700;color:#111827;line-height:1.3;">'.e((string) $this->name).'</div>'
            .'<div style="margin-top:2px;font-weight:600;color:#285854;">Nekustamo īpašumu aģents</div>'
            .'<div style="margin-top:7px;">'
            .'<a href="mailto:'.e((string) $this->email).'" style="color:#374151;text-decoration:none;">'.e((string) $this->email).'</a>'
            .'</div>';

        if ($phone !== null) {
            $html .= '<div style="margin-top:2px;">'
                .'<a href="tel:'.e((string) $phoneHref).'" style="color:#374151;text-decoration:none;">'.e($phone).'</a>'
                .'</div>';
        }

        return $html.'</td></tr></table>';
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
