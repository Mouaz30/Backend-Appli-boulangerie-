<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\CommandeService;
use App\Services\ProduitService;
use App\Services\PromotionService;
use App\Services\PdfService;
use App\Services\NotificationService;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CommandeService::class, function ($app) {
            return new CommandeService();
        });

        $this->app->singleton(ProduitService::class, function ($app) {
            return new ProduitService();
        });

        $this->app->singleton(PromotionService::class, function ($app) {
            return new PromotionService();
        });

        $this->app->singleton(PdfService::class, function ($app) {
            return new PdfService();
        });

        $this->app->singleton(NotificationService::class, function ($app) {
            return new NotificationService();
        });
    }

    public function boot(): void
    {
        //
    }
}
