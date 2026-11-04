<?php

namespace App\Providers;

use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::define('manage', fn ($user) => ($user->role ?? null) === 'admin');

        Table::configureUsing(fn (Table $table) => $table->stackedOnMobile());

        // Filtru poga paliek tabulas rīkjoslā blakus meklēšanai; filtru
        // panelis atveras modālā, tāpēc tas nepārklāj paziņojumus vai galveni.
        Table::configureUsing(fn (Table $table) => $table->filtersLayout(FiltersLayout::Modal));

        // Kolonnu pārvaldnieks ("Kolonnas") nav vajadzīgs nevienā tabulā.
        Table::configureUsing(fn (Table $table) => $table->columnManager(false));

        // Empty cells everywhere show "-" instead of blank space
        TextColumn::configureUsing(fn (TextColumn $column) => $column->placeholder('-'));

        // Filament's own lv translation is missing this key, so every empty
        // select dropdown falls back to English "No options available."
        Select::configureUsing(fn (Select $select) => $select->noOptionsMessage('Nav atrasti ieraksti.'));
    }
}
