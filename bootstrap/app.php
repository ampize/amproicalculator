<?php

require_once __DIR__.'/../vendor/autoload.php';

try {
    (new Dotenv\Dotenv(__DIR__.'/../'))->load();
} catch (Dotenv\Exception\InvalidPathException $e) {
    //
}


$app = new Laravel\Lumen\Application(
    realpath(__DIR__.'/../')
);

 $app->withFacades();



config([
    "resourceNamespaces"=>[
        "default"=>[
            "path"=>realpath(__DIR__.'/../resources/default/')
        ]
    ],
]);

$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    App\Exceptions\Handler::class
);

$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    App\Console\Kernel::class
);


 $app->register(App\Providers\AppServiceProvider::class);


$app->group(['namespace' => 'App\Http\Controllers'], function ($app) {
    $app->get('/resource/{namespace}/{path:.*}', "ResourceController@resolve");
});

return $app;
