<?php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}
    public function boot(): void
    {
        // Prevent common local/dev runtime errors on fresh clones (especially on Windows)
        // where storage/framework subfolders may not exist yet.
        // This is safe in production too (no-op if folders exist).
        foreach ([
            storage_path('framework/cache'),
            storage_path('framework/sessions'),
            storage_path('framework/views'),
        ] as $dir) {
            if (!is_dir($dir)) {
                @mkdir($dir, 0775, true);
            }
        }
    }
}
