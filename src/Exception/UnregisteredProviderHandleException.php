<?php

namespace ProxyIPManager\Exception;

use ProxyIPManager\Exception;

/**
 * An exception thrown when trying to using a provider with a handle that's not registered.
 */
class UnregisteredProviderHandleException extends Exception
{
    /**
     * The handle that's not registered.
     *
     * @var string
     */
    private $handle;

    /**
     * @param string $handle the handle that's not registered
     */
    public function __construct($handle)
    {
        $this->handle = $handle;
        parent::__construct(t('No provider with handle "%s" is registered.', $this->handle));
    }

    /**
     * Get the handle that's not registered.
     *
     * @return string
     */
    public function getHandle()
    {
        return $this->handle;
    }
}
