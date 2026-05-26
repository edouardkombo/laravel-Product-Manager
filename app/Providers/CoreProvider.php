<?php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Kernel\Core;

class CoreProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Core::class, function ($app) {
            $core = new Core();
            $entities = config('core.entities');
            foreach ($entities as $name => $def) {
                $core->add($name, $def['class'], $def['rules'] ?? []);
            }
            return $core;
        });
    }
}
