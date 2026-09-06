<?php

namespace App\Providers;

use App\Models\FlashSale;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
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
        Carbon::setLocale('id');

        // market.arahinn.com dilayani lewat HTTPS di belakang proxy; tanpa ini
        // aset dan tautan absolut bisa terbentuk sebagai http:// dan diblokir.
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        Blade::directive('rp', function (string $expression) {
            return "<?php echo rp($expression); ?>";
        });

        // Tautan flash sale di navigasi hanya muncul saat ada kampanye berjalan.
        // Dihitung lewat composer supaya layout tidak memanggil model sendiri,
        // dan hanya untuk layout yang benar-benar memakainya.
        View::composer('components.layouts.guest', function ($view) {
            $view->with('adaFlashSale', FlashSale::berlangsung()->exists());
        });
    }
}