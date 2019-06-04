<?php

namespace ProxyIPManager\Provider;

use ArrayAccess;

/**
 * The interface that all proxy IP providers must implement.
 */
interface ProviderInterface
{
    /**
     * Get the (localized) name of the provider.
     *
     * @return string
     */
    public function getName();

    /**
     * Retrieve the IPs (IPv4 and/or IPv6) of the proxy.
     * They can be single IP addresses (eg 1.2.3.4, ::1), as well as IP ranges in subnet notation (eg 127.0.0.1/8, ::/8).
     *
     * @param array|null $configuration the provider configuration (<code>null</code> if and only if the provider is not configurable)
     * @param \ArrayAccess $errors add here errors detected during the resolution
     *
     * @return string[]
     */
    public function getProxyIPs(ArrayAccess $errors, array $configuration = null);
}
