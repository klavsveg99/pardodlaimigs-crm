<?php

declare(strict_types=1);

namespace App\Filament\Admin\Pages;

use App\Filament\Forms\Components\AvatarEditor;
use App\Filament\Forms\Components\PhoneInput;
use App\Models\User;
use App\Rules\Phone;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

/**
 * "Mans profils" — ļauj arī ne-adminam (aģentam) pārvaldīt savus aģenta
 * datus (Vārds, E-pasts, Tālrunis, foto, amats, apraksts, sociālie tīkli,
 * biroja adrese). Publiskā profila daļa atbilst Aģenti-iekļautajiem laukiem.
 */
class MyProfile extends Page
{
    /**
     * @var array<string, mixed> | null
     */
    public ?array $data = [];

    protected static ?string $slug = 'mans-profils';

    protected string $view = 'filament.pages.my-profile';

    protected static ?string $navigationLabel = 'Mans profils';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user-circle';

    protected static ?int $navigationSort = -1;

    public static function shouldRegisterNavigation(): bool
    {
        // Adminiem nav vajadzīgs — viņiem ir pilna Aģenti sadaļa.
        return static::canAccess() && auth()->user()?->can('manage') === false;
    }

    public static function canAccess(): bool
    {
        return auth()->check();
    }

    public function getUser(): Model
    {
        $user = auth()->user();

        abort_unless($user instanceof User, 403);

        return $user;
    }

    public function mount(): void
    {
        $user = $this->getUser();

        $this->form->fill(collect(['name', 'email', 'avatar_path', 'phone', 'position', 'description', 'facebook_url', 'instagram_url', 'linkedin_url', 'website_url', 'office_address'])
            ->mapWithKeys(fn (string $key): array => [$key => $user->{$key}])
            ->all());
    }

    public function getTitle(): string
    {
        return 'Mans profils';
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema
            ->model($this->getUser())
            ->operation('edit')
            ->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Pamatdati')->schema([
                    TextInput::make('name')->label('Vārds')->required()->maxLength(255),
                    TextInput::make('email')->label('E-pasts')->email()->required()->maxLength(255)
                        ->unique(table: User::class, ignoreRecord: true),
                ])->columns(2),

                Section::make('Parole')->schema([
                    TextInput::make('password')
                        ->label('Jauna parole')
                        ->hint('Atstāj tukšu, ja nemainīt')
                        ->password()
                        ->revealable()
                        ->dehydrated(fn ($state) => filled($state))
                        ->maxLength(255)
                        ->confirmed(),
                    TextInput::make('password_confirmation')
                        ->label('Jauna parole atkārtoti')
                        ->password()
                        ->revealable()
                        ->dehydrated(false)
                        ->maxLength(255),
                ])->columns(2),

                Section::make('Aģenta publiskais profils')->schema([
                    AvatarEditor::make('avatar_path')
                        ->label('Foto')
                        ->helperText('Kvadrātveida foto — 1:1, 1000×1000px, max 5MB. Izmanto redaktoru, lai apgrieztu un apvērstu.')
                        ->columnSpanFull(),
                    PhoneInput::make('phone')->label('Tālrunis')->maxLength(20)->rule(new Phone),
                    TextInput::make('position')->label('Amats')->maxLength(255)->placeholder('Aģents'),
                    Textarea::make('description')->label('Apraksts')->rows(4)->maxLength(2000)->helperText('Rādās aģenta lapā un īpašuma kontaktblokā')->columnSpanFull(),
                    TextInput::make('facebook_url')->label('Facebook URL')->url()->maxLength(500)->columnSpan(1),
                    TextInput::make('instagram_url')->label('Instagram URL')->url()->maxLength(500)->columnSpan(1),
                    TextInput::make('linkedin_url')->label('LinkedIn URL')->url()->maxLength(500)->columnSpan(1),
                    TextInput::make('website_url')->label('Mājaslapa')->url()->maxLength(500)->columnSpan(1),
                    TextInput::make('office_address')->label('Biroja adrese')->maxLength(500)->placeholder('Rīga, Brīvības iela 1')->columnSpanFull(),
                ])->columns(2),
            ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    \Filament\Schemas\Components\Actions::make([$this->getSaveFormAction()]),
                ]),
        ]);
    }

    protected function getSaveFormAction(): Action
    {
        return Action::make('save')
            ->label('Saglabāt')
            ->color('primary')
            ->submit('save')
            ->keyBindings(['mod+s']);
    }

    public function save(): void
    {
        $data = $this->form->getState();

        /** @var User $user */
        $user = $this->getUser();
        $user->update($data);

        $this->form->fill($user->only([
            'name', 'email', 'avatar_path', 'phone', 'position',
            'description', 'facebook_url', 'instagram_url', 'linkedin_url',
            'website_url', 'office_address',
        ]));

        Notification::make()
            ->title('Profils saglabāts')
            ->success()
            ->send();
    }
}

