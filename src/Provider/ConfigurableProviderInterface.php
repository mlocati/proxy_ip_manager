<?php

namespace ProxyIPManager\Provider;

use ArrayAccess;
use Concrete\Core\Page\Page;

/**
 * The interface that configurable proxy IP providers must implement.
 */
interface ConfigurableProviderInterface extends ProviderInterface
{
    /**
     * Get the default configuration for the provider.
     *
     * @return array
     */
    public function getDefaultConfiguration();

    /**
     * Get the element to be used to configure the provider in the dashboard.
     *
     * @param array $configuration the provider configuration
     * @param \Concrete\Core\Page\Page $page
     *
     * @return \Concrete\Core\Filesystem\Element
     */
    public function getConfigurationElement(array $configuration, Page $page);

    /**
     * Check / normalize the data received from the configuration element.
     *
     * @param array $data the raw data received from the configuration element
     * @param \ArrayAccess $errors errors detected in the process will be added here
     *
     * @return array the normalized configuration to be saved/used
     */
    public function checkConfiguration(array $data, ArrayAccess $errors);
}
