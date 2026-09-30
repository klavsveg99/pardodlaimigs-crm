<?php

namespace App\Providers;

use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
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

        // Empty cells everywhere show "—" instead of blank space
        TextColumn::configureUsing(fn (TextColumn $column) => $column->placeholder('—'));

        // Filament's own lv translation is missing this key, so every empty
        // select dropdown falls back to English "No options available."
        Select::configureUsing(fn (Select $select) => $select->noOptionsMessage('Nav atrasti ieraksti.'));
    }
}
