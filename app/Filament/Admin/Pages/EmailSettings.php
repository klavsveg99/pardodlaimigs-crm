<?php

declare(strict_types=1);

namespace App\Filament\Admin\Pages;

use App\Models\EmailSetting;
use App\Support\EmailShortcodes;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use UnitEnum;

/**
 * "Iestatījumi" — tikai administratoram. Rediģē automātisko e-pastu tekstus
 * (apsveikumi, uzdevuma paziņojums, kavētā uzdevuma atgādinājums), kas citādi
 * ir iekodēti un nav rediģējami e-pasta modāļos. Īskodus skat.
 * App\Support\EmailShortcodes.
 */
class EmailSettings extends Page
{
    /** @var array<string, mixed> | null */
    public ?array $data = [];

    protected static ?string $slug = 'iestatijumi';

    protected static ?string $navigationLabel = 'Iestatījumi';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static string|UnitEnum|null $navigationGroup = 'Sistēma';

    protected static ?int $navigationSort = 20;

    protected string $view = 'filament.pages.email-settings';

    public static function canAccess(): bool
    {
        return auth()->user()?->can('manage') ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public function getTitle(): string
    {
        return 'Iestatījumi';
    }

    public function mount(): void
    {
        $this->form->fill(EmailSetting::current()->data ?? []);
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema
            ->model(EmailSetting::current())
            ->operation('edit')
            ->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Apsveikumi')
                    ->description(EmailShortcodes::hint(EmailShortcodes::GREETING_LABELS))
                    ->columns(2)
                    ->schema([
                        Toggle::make('greetings.enabled')
                            ->label('Automātiski sūtīt apsveikumus')
                            ->columnSpanFull(),
                        TextInput::make('greetings.birthday.subject')
                            ->label('Dzimšanas diena - temats')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Textarea::make('greetings.birthday.body')
                            ->label('Dzimšanas diena - teksts')
                            ->rows(3)
                            ->columnSpanFull(),
                        TextInput::make('greetings.name_day.subject')
                            ->label('Vārdadiena - temats')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Textarea::make('greetings.name_day.body')
                            ->label('Vārdadiena - teksts')
                            ->rows(3)
                            ->columnSpanFull(),
                        Repeater::make('greetings.holidays')
                            ->label('Svētki')
                            ->addActionLabel('Pievienot svētkus')
                            ->columns(2)
                            ->reorderable(false)
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['label'] ?? null)
                            ->schema([
                                TextInput::make('date')
                                    ->label('Datums')
                                    ->placeholder('DD.MM')
                                    ->required()
                                    ->rules(['regex:/^\d{2}\.\d{2}$/']),
                                TextInput::make('label')
                                    ->label('Nosaukums')
                                    ->required()
                                    ->maxLength(100),
                                TextInput::make('subject')
                                    ->label('Temats')
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpanFull(),
                                Textarea::make('body')
                                    ->label('Teksts')
                                    ->rows(2)
                                    ->columnSpanFull(),
                            ])
                            ->columnSpanFull(),
                    ]),

                Section::make('Uzdevuma paziņojums')
                    ->description(EmailShortcodes::hint(EmailShortcodes::TASK_LABELS))
                    ->columns(1)
                    ->schema([
                        TextInput::make('task_notification.subject')
                            ->label('Temats')
                            ->required()
                            ->maxLength(255),
                        Textarea::make('task_notification.body')
                            ->label('Teksts')
                            ->rows(4)
                            ->helperText('Katrs tukšais lauciņš (rinda) kļūst par atsevišķu rindkopu. Uzdevuma dati tiek pievienoti automātiski.'),
                    ]),

                Section::make('Kavētā uzdevuma atgādinājums')
                    ->description(EmailShortcodes::hint(EmailShortcodes::OVERDUE_LABELS))
                    ->columns(1)
                    ->schema([
                        TextInput::make('overdue_reminder.subject')
                            ->label('Temats')
                            ->required()
                            ->maxLength(255),
                        Textarea::make('overdue_reminder.body')
                            ->label('Teksts')
                            ->rows(3),
                    ]),
            ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([$this->getSaveFormAction()]),
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

        $setting = EmailSetting::current();
        $setting->update(['data' => $data]);

        $this->form->fill($setting->fresh()->data ?? []);

        Notification::make()
            ->title('Iestatījumi saglabāti')
            ->success()
            ->send();
    }
}
