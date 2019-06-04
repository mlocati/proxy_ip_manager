<?php

namespace ProxyIPManager\Exception;

use ProxyIPManager\Exception;
use ProxyIPManager\Provider\ProviderInterface;

/**
 * An exception thrown when trying to register a provider that does not implement the ProviderInterface interface.
 */
class NotAProviderException extends Exception
{
    /**
     * The object that does not implement the ProviderInterface.
     *
     * @var object
     */
    private $object;

    /**
     * @param object $object the object that does not implement the ProviderInterface
     */
    public function __construct($object)
    {
        $this->object = $object;
        parent::__construct(t('The class "%s" does not implement the interface "%s".', get_class($this->object), ProviderInterface::class));
    }

    /**
     * Get object that does not implement the ProviderInterface.
     *
     * @return object
     */
    public function getObject()
    {
        return $this->object;
    }
}
