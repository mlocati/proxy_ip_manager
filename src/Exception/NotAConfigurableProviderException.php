<?php

namespace ProxyIPManager\Exception;

use ProxyIPManager\Exception;
use ProxyIPManager\Provider\ConfigurableProviderInterface;
use ProxyIPManager\Provider\ProviderInterface;

/**
 * An exception thrown when trying configure a non-configurable provider.
 */
class NotAConfigurableProviderException extends Exception
{
    /**
     * The provider that does not implement the ConfigurableProviderInterface.
     *
     * @var \ProxyIPManager\Provider\ProviderInterface
     */
    private $provider;

    /**
     * @param \ProxyIPManager\Provider\ProviderInterface $provider the provider that does not implement the ConfigurableProviderInterface
     */
    public function __construct(ProviderInterface $provider)
    {
        $this->provider = $provider;
        parent::__construct(t('The class "%s" does not implement the interface "%s".', get_class($this->provider), ConfigurableProviderInterface::class));
    }

    /**
     * Get the provider that does not implement the ConfigurableProviderInterface.
     *
     * @return \ProxyIPManager\Provider\ProviderInterface
     */
    public function getProvider()
    {
        return $this->provider;
    }
}
