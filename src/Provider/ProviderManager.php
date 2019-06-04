<?php

namespace ProxyIPManager\Provider;

use Concrete\Core\Application\Application;
use Concrete\Core\Config\Repository\Repository;
use Exception;
use ProxyIPManager\Exception\DuplicatedProviderHandleException;
use ProxyIPManager\Exception\NotAConfigurableProviderException;
use ProxyIPManager\Exception\NotAProviderException;
use ProxyIPManager\Exception\ProviderNotCreatableException;
use ProxyIPManager\Exception\UnregisteredProviderHandleException;

class ProviderManager
{
    /**
     * @var \Concrete\Core\Application\Application
     */
    protected $app;

    /**
     * @var \Concrete\Core\Config\Repository\Repository
     */
    protected $config;

    /**
     * The list of registered providers.
     *
     * @var \ProxyIPManager\Provider\ProviderInterface[]|string[] Array keys are the handles, array values are the class names/aliases
     */
    protected $providers = [];

    /**
     * Initialize the instance.
     *
     * @param \Concrete\Core\Config\Repository\Repository $config
     * @param \Concrete\Core\Application\Application $app
     */
    public function __construct(Application $app, Repository $config)
    {
        $this->app = $app;
        $this->config = $config;
        $this->registerConfiguredProviders();
    }

    /**
     * Register a new provider.
     *
     * @param string $handle the unique handle identifying the provider
     * @param string $abstract the class names/aliases to be registered
     * @param bool $enabled Enable the provider?
     *
     * @throws \ProxyIPManager\Exception\DuplicatedProviderHandleException if another provider is registerd with the handle $handle
     * @throws \ProxyIPManager\Exception\ProviderNotCreatableException when errors occur while creating the provider instance
     * @throws \ProxyIPManager\Exception\NotAProviderException if $abstract does not resolve to a ProviderInterface instance
     *
     * @return $this
     */
    public function registerProvider($handle, $abstract, $enabled)
    {
        if (isset($this->providers[$handle])) {
            throw new DuplicatedProviderHandleException($handle);
        }
        try {
            $provider = $this->app->make($abstract);
        } catch (Exception $creationException) {
            throw new ProviderNotCreatableException($abstract, $creationException);
        }
        if (!$provider instanceof ProviderInterface) {
            throw new NotAProviderException($provider);
        }
        $this->providers[$handle] = $provider;
        $providers = $this->config->get('proxy_ip_manager::providers');
        $providers[$handle] = [
            'abstract' => $abstract,
            'enabled' => (bool) $enabled,
        ];
        $this->config->set('proxy_ip_manager::providers', $providers);
        $this->config->save('proxy_ip_manager::providers', $providers);

        return $this;
    }

    /**
     * Unregister a provider.
     * If no provider with the specified handle exists, nothing will be done.
     *
     * @param string $handle
     *
     * @return $this
     */
    public function unregisterProvider($handle)
    {
        if (!isset($this->providers[$handle])) {
            return $this;
        }
        $providers = $this->config->get('proxy_ip_manager::providers');
        if (!isset($providers[$handle])) {
            return $this;
        }
        unset($providers[$handle]);
        $this->config->set('proxy_ip_manager::providers', $providers);
        $this->config->save('proxy_ip_manager::providers', $providers);

        return $this;
    }

    /**
     * Get a registered provider given its handle.
     *
     * @param string $handle
     *
     * @throws \ProxyIPManager\Exception\ProviderNotCreatableException when errors occur while creating the provider instance
     * @throws \ProxyIPManager\Exception\NotAProviderException if the registered provider does not resolve to a ProviderInterface instance
     *
     * @return \ProxyIPManager\Provider\ProviderInterface|null returns <code>null</code> if no provider with the specified handle is registered
     */
    public function getProviderByHandle($handle)
    {
        if (!isset($this->providers[$handle])) {
            return null;
        }
        $abstract = $this->providers[$handle];
        if ($abstract instanceof ProviderInterface) {
            return $abstract;
        }
        $provider = $this->app->make($abstract);
        if (!$provider instanceof ProviderInterface) {
            throw new NotAProviderException($provider);
        }
        $this->providers[$handle] = $provider;

        return $provider;
    }

    /**
     * Get all the providers.
     *
     * @param bool|null $enabled set to true to return only the enabled providers, false to return only the disabled providers, null to return all the providers
     *
     * @throws \ProxyIPManager\Exception\ProviderNotCreatableException when errors occur while creating one of the provider instances
     * @throws \ProxyIPManager\Exception\NotAProviderException if one of the registered providers does not resolve to a ProviderInterface instance
     *
     * @return \ProxyIPManager\Provider\ProviderInterface[] array keys are the provider handles
     */
    public function getProviders($enabled = null)
    {
        $list = $this->providers;
        if ($enabled !== null) {
            $providers = $this->config->get('proxy_ip_manager::providers');
            foreach (array_keys($list) as $handle) {
                if (empty($providers[$handle]['enabled'])) {
                    if ($enabled) {
                        unset($list[$handle]);
                    }
                } else {
                    if (!$enabled) {
                        unset($list[$handle]);
                    }
                }
            }
        }
        $result = [];
        foreach (array_keys($list) as $handle) {
            $result[$handle] = $this->getProviderByHandle($handle);
        }

        return $result;
    }

    /**
     * Check if a provider is enabled.
     *
     * @param string $handle
     *
     * @return bool|null returns <code>null</code> if no provider with the specified handle is registered
     */
    public function isProviderEnabled($handle)
    {
        if (!isset($this->providers[$handle])) {
            return null;
        }
        $providers = $this->config->get('proxy_ip_manager::providers');

        return !empty($providers[$handle]['enabled']);
    }

    /**
     * Enable/disable a provider.
     *
     * @param string $handle The handle of the provider
     * @param bool $enabled true to enable the provider, false to disable it
     *
     * @throws \ProxyIPManager\Exception\UnregisteredProviderHandleException if no provider with the specified handle is registered
     *
     * @return $this
     */
    public function setProviderEnabled($handle, $enabled)
    {
        if (!isset($this->providers[$handle])) {
            throw new UnregisteredProviderHandleException($handle);
        }
        $providers = $this->config->get('proxy_ip_manager::providers');
        $providers[$handle]['enabled'] = (bool) $enabled;
        $this->config->set('proxy_ip_manager::providers', $providers);
        $this->config->save('proxy_ip_manager::providers', $providers);

        return $this;
    }

    /**
     * Get the configuration of a configurable provider.
     *
     * @param string $handle The handle of the provider
     *
     * @throws \ProxyIPManager\Exception\UnregisteredProviderHandleException if no provider with the specified handle is registered
     * @throws \ProxyIPManager\Exception\ProviderNotCreatableException when errors occur while creating the provider instance
     * @throws \ProxyIPManager\Exception\NotAProviderException if the registered provider does not resolve to a ProviderInterface instance
     *
     * @return array|null return <code>null</code> if the provider is not configurable, an array otherwise
     */
    public function getProviderConfiguration($handle)
    {
        $provider = $this->getProviderByHandle($handle);
        if ($provider === null) {
            throw new UnregisteredProviderHandleException($handle);
        }
        if (!$provider instanceof ConfigurableProviderInterface) {
            return null;
        }
        $providers = $this->config->get('proxy_ip_manager::providers');
        if (isset($providers[$handle]['configuration'])) {
            return $providers[$handle]['configuration'];
        }

        return $provider->getDefaultConfiguration();
    }

    /**
     * Get the IPs lastly retrieved by a provider.
     *
     * @param string $handle The handle of the provider
     *
     * @throws \ProxyIPManager\Exception\UnregisteredProviderHandleException if no provider with the specified handle is registered
     *
     * @return string[]
     */
    public function getProviderLastIPs($handle)
    {
        $providers = $this->config->get('proxy_ip_manager::providers');
        if (!isset($providers[$handle])) {
            throw new UnregisteredProviderHandleException($handle);
        }

        return isset($providers[$handle]['lastIPs']) ? $providers[$handle]['lastIPs'] : [];
    }

    /**
     * Set the IPs lastly retrieved by a provider.
     *
     * @param string $handle The handle of the provider
     * @param string[] $ips
     *
     * @throws \ProxyIPManager\Exception\UnregisteredProviderHandleException if no provider with the specified handle is registered
     *
     * @return $this
     */
    public function setProviderLastIPs($handle, array $ips)
    {
        $providers = $this->config->get('proxy_ip_manager::providers');
        if (!isset($providers[$handle])) {
            throw new UnregisteredProviderHandleException($handle);
        }
        $providers[$handle]['lastIPs'] = $ips;
        $this->config->set('proxy_ip_manager::providers', $providers);
        $this->config->save('proxy_ip_manager::providers', $providers);

        return $this;
    }

    /**
     * @param string $handle The handle of the provider
     * @param array $configuration the provider configuration
     *
     * @throws \ProxyIPManager\Exception\UnregisteredProviderHandleException if no provider with the specified handle is registered
     * @throws \ProxyIPManager\Exception\NotAConfigurableProviderException if the provider is not configurable
     * @throws \ProxyIPManager\Exception\ProviderNotCreatableException when errors occur while creating the provider instance
     * @throws \ProxyIPManager\Exception\NotAProviderException if the registered provider does not resolve to a ProviderInterface instance
     *
     * @return $this
     */
    public function setProviderConfiguration($handle, array $configuration)
    {
        $provider = $this->getProviderByHandle($handle);
        if ($provider === null) {
            throw new UnregisteredProviderHandleException($handle);
        }
        if (!$provider instanceof ConfigurableProviderInterface) {
            throw new NotAConfigurableProviderException($provider);
        }
        $providers = $this->config->get('proxy_ip_manager::providers');
        $providers[$handle]['configuration'] = $configuration;
        $this->config->set('proxy_ip_manager::providers', $providers);
        $this->config->save('proxy_ip_manager::providers', $providers);

        return $this;
    }

    /**
     * Load the already registered providers.
     *
     * @return $this
     */
    protected function registerConfiguredProviders()
    {
        foreach ($this->config->get('proxy_ip_manager::providers') as $handle => $data) {
            $this->providers[$handle] = $data['abstract'];
        }

        return $this;
    }
}
