<?php

use Illuminate\Foundation\Application;
use Illuminate\Contracts\Http\Kernel as HttpKernelContract;
use Illuminate\Contracts\Console\Kernel as ConsoleKernelContract;
use Illuminate\Contracts\Debug\ExceptionHandler as ExceptionHandlerContract;

$app = new Application(
    $_ENV['APP_BASE_PATH'] ?? dirname(__DIR__)
);

/*
|--------------------------------------------------------------------------
| Bind Important Interfaces
|--------------------------------------------------------------------------
|
| This binds the interfaces into the container so we can resolve them.
|
*/

$app->singleton(
    HttpKernelContract::class,
    App\Http\Kernel::class
);

$app->singleton(
    ConsoleKernelContract::class,
    App\Console\Kernel::class
);

$app->singleton(
    ExceptionHandlerContract::class,
    App\Exceptions\Handler::class
);

return $app;
