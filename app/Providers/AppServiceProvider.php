<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        Livewire::forceAssetInjection();
    }

    public function boot(): void
    {
        //
    }
}
