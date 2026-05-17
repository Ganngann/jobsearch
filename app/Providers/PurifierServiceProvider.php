<?php

namespace App\Providers;

use HTMLPurifier;
use HTMLPurifier_Config;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class PurifierServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(HTMLPurifier::class, function ($app) {
            $config = HTMLPurifier_Config::createDefault();

            $cachePath = storage_path('app/purifier');
            if (!is_dir($cachePath)) {
                mkdir($cachePath, 0755, true);
            }
            $config->set('Cache.SerializerPath', $cachePath);

            // Basic secure configuration - allows common text formatting and structure
            $config->set('HTML.Allowed', 'div,b,strong,i,em,u,a[href|title],ul,ol,li,p[style],br,span[style],h1,h2,h3,h4,h5,h6');
            $config->set('CSS.AllowedProperties', 'font,font-size,font-weight,font-style,font-family,text-decoration,padding-left,color,background-color,text-align');
            $config->set('AutoFormat.AutoParagraph', true);
            $config->set('AutoFormat.RemoveEmpty', true);

            return new HTMLPurifier($config);
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        Blade::directive('purify', function ($expression) {
            return "<?php echo app(HTMLPurifier::class)->purify($expression); ?>";
        });
    }
}
