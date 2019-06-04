<?php

namespace ProxyIPManager;

use Concrete\Core\Application\Application;
use Concrete\Core\Foundation\Service\Provider;
use ProxyIPManager\Provider\ProviderManager;

/**
 * Class that registers the services.
 */
class ServiceProvider extends Provider
{
    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Foundation\Service\Provider::register()
     */
    public function register()
    {
        $this->app->bind(\Concrete\Controller\SinglePage\Dashboard\System\Permissions\TrustedProxies::class, function (Application $app, array $parameters) {
            return $app->make(Override\TrustedProxiesController::class, $parameters);
        });
        $this->app->singleton(ProviderManager::class, function (Application $app) {
            return $app->build(ProviderManager::class);
        });
    }
}
