<?php

namespace App\Providers;

use App\Models\MetodePembayaran;
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

        // Lencana metode pembayaran di footer dibaca dari tabelnya, sehingga
        // metode yang dinonaktifkan atau belum berisi nomor ikut hilang dari
        // sana tanpa perlu disunting di dua tempat. Dipasang sebagai composer
        // agar kuerinya hanya berjalan untuk layout yang memakainya.
        View::composer('components.layouts.guest', function ($view) {
            $view->with('metodeBayar', MetodePembayaran::siap()->orderBy('tipe')->orderBy('nama')->get());
        });
    }
}