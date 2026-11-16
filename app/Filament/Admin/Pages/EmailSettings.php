<?php

declare(strict_types=1);

namespace App\Filament\Admin\Pages;

use App\Filament\Admin\Widgets\GreetingHistoryTable;
use App\Filament\Admin\Widgets\OfferHistoryTable;
use App\Models\EmailSetting;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use UnitEnum;

/**
 * "Iestatījumi" - tikai administratoram. Rediģē automātisko apsveikumu
 * e-pastu tekstus (dzimšanas diena, vārdadiena, svētki) un piedāvājumu
 * e-pasta noklusējumu. Uzdevuma paziņojums paliek iekodēts Task modelī.
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
        // Merge defaults so settings saved before a new section existed still
        // open with a usable subject/body instead of empty fields. Only
        // top-level keys are merged - an intentionally emptied holidays list
        // must stay empty instead of being replaced by the config defaults.
        $defaults = EmailSetting::defaults();
        $data = EmailSetting::current()->data ?? [];

        if (! is_array($data)) {
            $data = [];
        }

        foreach (['greetings', 'offers', 'properties'] as $key) {
            $data[$key] = array_replace(
                $defaults[$key],
                is_array($data[$key] ?? null) ? $data[$key] : [],
            );
        }

        $this->form->fill($data);
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
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Toggle::make('greetings.enabled')
                            ->label('Automātiski sūtīt apsveikumus'),

                        Section::make('Dzimšanas diena')
                            ->columns(1)
                            ->schema([
                                TextInput::make('greetings.birthday.subject')
                                    ->label('Temats')
                                    ->required()
                                    ->maxLength(255),
                                Textarea::make('greetings.birthday.body')
                                    ->label('Teksts')
                                    ->rows(3),
                            ]),

                        Section::make('Vārda diena')
                            ->columns(1)
                            ->schema([
                                TextInput::make('greetings.name_day.subject')
                                    ->label('Temats')
                                    ->required()
                                    ->maxLength(255),
                                Textarea::make('greetings.name_day.body')
                                    ->label('Teksts')
                                    ->rows(3),
                            ]),

                        Section::make('Svētki')
                            ->columns(1)
                            ->schema([
                                Repeater::make('greetings.holidays')
                                    ->hiddenLabel()
                                    ->extraAttributes(['class' => 'pdc-holidays'])
                                    ->addActionLabel('Pievienot svētkus')
                                    ->columns(2)
                                    ->reorderable(false)
                                    ->collapsible()
                                    ->collapseAllAction(fn (Action $action): Action => $action->hidden())
                                    ->expandAllAction(fn (Action $action): Action => $action->hidden())
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

                        Livewire::make(GreetingHistoryTable::class)
                            ->columnSpanFull(),
                    ]),

                Section::make('Piedāvājumu nosūtīšana')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Toggle::make('offers.auto_send')
                            ->label('Automātiski sūtīt piedāvājumus atrastajiem objektiem')
                            ->helperText('Kad ieslēgts, Meklētāji automātiski nosūta piedāvājumu katram jaunatrastajam objektam, kuram portāls norāda kontaktpersonas e-pastu (city24.lv). Objektiem bez e-pasta piedāvājums jānosūta manuāli.'),

                        TextInput::make('offers.subject')
                            ->label('Temats')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Šis ir noklusējuma saturs visiem aģentiem - rakstiet neitrāli, bez personīgām atsauksmēm.'),

                        RichEditor::make('offers.body')
                            ->label('Teksts')
                            ->helperText('E-pasta kājenē jau automātiski tiek iekļauta mājaslapa pardodlaimigs.lv. Tālruņa un e-pasta kontakti kājenē netiek pievienoti - ja tie nepieciešami, iekļaujiet tos tekstā.')
                            ->columnSpanFull(),

                        Placeholder::make('offers.footer_note')
                            ->hiddenLabel()
                            ->content('Piedāvājuma e-pasts tiek sūtīts ar to pašu CRM e-pasta sagatavi. Manuāli nosūtot, tiek pievienots aģenta paraksts; automātiskajiem piedāvājumiem personīgais paraksts netiek pievienots. Aģenti saturu pirms nosūtīšanas var brīvi labot.'),

                        Livewire::make(OfferHistoryTable::class)
                            ->columnSpanFull(),
                    ]),

                Section::make('Īpašumi')
                    ->schema([
                        Select::make('properties.frontend_sort')
                            ->label('Īpašumu kārtošana mājaslapā')
                            ->options(EmailSetting::PROPERTY_SORTS)
                            ->default('featured_sort_order')
                            ->selectablePlaceholder(false)
                            ->required(),
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
