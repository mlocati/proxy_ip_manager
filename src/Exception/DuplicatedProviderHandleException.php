<?php

namespace ProxyIPManager\Exception;

use ProxyIPManager\Exception;

/**
 * An exception thrown when trying to register a provider with a handle already registered.
 */
class DuplicatedProviderHandleException extends Exception
{
    /**
     * The duplicated provider handle.
     *
     * @var string
     */
    private $handle;

    /**
     * @param string $handle the duplicated provider handle
     */
    public function __construct($handle)
    {
        $this->handle = $handle;
        parent::__construct(t('Another provider with handle "%s" is already registered.', $handle));
    }

    /**
     * Get the duplicated provider handle.
     *
     * @return string
     */
    public function getHandle()
    {
        return $this->handle;
    }
}
