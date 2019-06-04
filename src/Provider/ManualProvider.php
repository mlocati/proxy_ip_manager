<?php

namespace ProxyIPManager\Provider;

use ArrayAccess;
use Concrete\Core\Filesystem\ElementManager;
use Concrete\Core\Page\Page;
use IPLib\Factory;
use IPLib\Range\Pattern;

class ManualProvider implements ConfigurableProviderInterface
{
    /**
     * @var \Concrete\Core\Filesystem\ElementManager
     */
    protected $elementManager;

    /**
     * @param \Concrete\Core\Filesystem\ElementManager $elementManager
     */
    public function __construct(ElementManager $elementManager)
    {
        $this->elementManager = $elementManager;
    }

    /**
     * {@inheritdoc}
     *
     * @see \ProxyIPManager\Provider\ProviderInterface::getName()
     */
    public function getName()
    {
        return t('Manual list of IP addresses');
    }

    /**
     * {@inheritdoc}
     *
     * @see \ProxyIPManager\Provider\ProviderInterface::getProxyIPs()
     */
    public function getProxyIPs(ArrayAccess $errors, array $configuration = null)
    {
        return $configuration['ips'];
    }

    /**
     * {@inheritdoc}
     *
     * @see \ProxyIPManager\Provider\ConfigurableProviderInterface::getDefaultConfiguration()
     */
    public function getDefaultConfiguration()
    {
        return [
            'ips' => [
            ],
        ];
    }

    /**
     * {@inheritdoc}
     *
     * @see \ProxyIPManager\Provider\ConfigurableProviderInterface::getConfigurationElement()
     */
    public function getConfigurationElement(array $configuration, Page $page)
    {
        return $this->elementManager->get('configure_manual_provider', 'proxy_ip_manager', $page, ['configuration' => $configuration]);
    }

    /**
     * {@inheritdoc}
     *
     * @see \ProxyIPManager\Provider\ConfigurableProviderInterface::checkConfiguration()
     */
    public function checkConfiguration(array $data, ArrayAccess $errors)
    {
        $ips = [];
        if (isset($data['ips']) && is_string($data['ips'])) {
            foreach (preg_split('/\s+/', $data['ips'], -1, PREG_SPLIT_NO_EMPTY) as $ip) {
                $range = Factory::rangeFromString($ip);
                if ($range === null || $range instanceof Pattern) {
                    $errors[] = t('The IP address "%s" is not valid.', $ip);
                } else {
                    $ip = (string) $ip;
                    if (!in_array($ip, $ips, true)) {
                        $ips[] = $ip;
                    }
                }
            }
        }

        return [
            'ips' => $ips,
        ];
    }
}
