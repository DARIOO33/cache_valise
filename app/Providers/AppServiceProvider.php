<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // The interface is Bootstrap 5 (section 5), not Tailwind.
        Paginator::useBootstrapFive();
    }
}
