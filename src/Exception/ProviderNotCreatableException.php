<?php

namespace ProxyIPManager\Exception;

use Exception as BaseException;
use ProxyIPManager\Exception;

/**
 * An exception thrown when trying to create an object given its abstract representation.
 */
class ProviderNotCreatableException extends Exception
{
    /**
     * The abstract representation that could not be created.
     *
     * @var string
     */
    private $abstract;

    /**
     * The exception thrown while creating the object.
     *
     * @var \Exception
     */
    private $creationException;

    /**
     * @param string $abstract the abstract representation that could not be created
     * @param \Exception $creationException the exception thrown while creating the object
     */
    public function __construct($abstract, BaseException $creationException)
    {
        $this->abstract = $abstract;
        $this->creationException = $creationException;
        parent::__construct(t('The following error occurred while resolving "%1$s": %2$s', $this->abstract, $this->creationException->getMessage()), null, $creationException);
    }

    /**
     * Get the abstract representation that could not be created.
     *
     * @return string
     */
    public function getAbstract()
    {
        return $this->abstract;
    }

    /**
     * Get the exception thrown while creating the object.
     *
     * @return \Exception
     */
    public function getCreationException()
    {
        return $this->creationException;
    }
}
