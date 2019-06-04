<?php

namespace ProxyIPManager;

use ArrayObject;
use Concrete\Core\Config\Repository\Repository;
use Concrete\Core\Logging\Channels;
use Concrete\Core\Logging\LoggerAwareInterface;
use Concrete\Core\Logging\LoggerAwareTrait;
use Exception;
use IPLib\Factory;
use IPLib\Range\Pattern;
use ProxyIPManager\Provider\ProviderInterface;
use ProxyIPManager\Provider\ProviderManager;
use Psr\Log\LogLevel;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

/**
 * Class that updates the proxy IP addresses.
 */
class Updater implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var \ProxyIPManager\Provider\ProviderManager
     */
    protected $providerManager;

    /**
     * @var \Concrete\Core\Config\Repository\Repository
     */
    protected $config;

    /**
     * @var \Symfony\Component\Console\Output\OutputInterface|null
     */
    protected $attachedConsole;

    /**
     * @param \ProxyIPManager\Provider\ProviderManager $providerManager
     * @param \Concrete\Core\Config\Repository\Repository $config
     * @param \Concrete\Core\Application\Application $app
     */
    public function __construct(ProviderManager $providerManager, Repository $config)
    {
        $this->providerManager = $providerManager;
        $this->config = $config;
    }

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Logging\LoggerAwareInterface::getLoggerChannel()
     */
    public function getLoggerChannel()
    {
        return Channels::CHANNEL_NETWORK;
    }

    /**
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *
     * @return $this
     */
    public function attachConsole(OutputInterface $output)
    {
        $this->attachedConsole = $output;

        return $this;
    }

    /**
     * Process the enabled proxy IP providers.
     */
    public function processEnabledProviders()
    {
        try {
            $this->write(LogLevel::DEBUG, '# ' . t('Listing enabled providers'));
            $providers = $this->providerManager->getProviders(true);
            $ips = [];
            if (empty($providers)) {
                $this->write(LogLevel::DEBUG, t('No enable providers found.'));
            } else {
                foreach ($providers as $handle => $provider) {
                    $ips = array_merge($ips, $this->processProvider($handle, $provider));
                }
            }
            $this->write(LogLevel::DEBUG, '# ' . t('Saving final list of IPs'));
            $this->applyIPs(array_values(array_unique($ips)));
        } catch (Exception $x) {
            $this->write(LogLevel::CRITICAL, $x->getMessage());
        } catch (Throwable $x) {
            $this->write(LogLevel::CRITICAL, $x->getMessage());
        } finally {
            $this->write(LogLevel::DEBUG, '# ' . t('Saving date/time of current execution'));
            $now = time();
            $this->config->set('proxy_ip_manager::updates.last_run', $now);
            $this->config->save('proxy_ip_manager::updates.last_run', $now);
        }
    }

    /**
     * Fetch the IPs from a specific provider.
     *
     * @param string $handle
     * @param \ProxyIPManager\Provider\ProviderInterface $provider
     *
     * @return string[]
     */
    protected function processProvider($handle, ProviderInterface $provider)
    {
        $this->write(LogLevel::INFO, '> ' . t('Invoking provider "%s"', $handle));
        $oldIPs = $this->providerManager->getProviderLastIPs($handle);
        $errors = new ArrayObject();
        $ips = $provider->getProxyIPs($errors, $this->providerManager->getProviderConfiguration($handle));
        foreach ($errors as $error) {
            $this->write(LogLevel::ERROR, $error);
        }
        $this->write(LogLevel::DEBUG, t('Resulting IPs: %s', implode(' ', $ips)));
        $ips = $this->checkIPList($ips);
        $this->write(LogLevel::DEBUG, t('Resulting IPs after normalization: %s', implode(' ', $ips)));
        $this->providerManager->setProviderLastIPs($handle, $ips);
        $this->checkChangedIPs($handle, $oldIPs, $ips);

        return $ips;
    }

    /**
     * Should a specific LogLevel be written to the console?
     *
     * @param string $logLevel One of the LogLevel constants
     *
     * @return bool
     */
    protected function shouldWriteToConsole($logLevel)
    {
        if ($this->attachedConsole === null) {
            return false;
        }
        $consoleVerbosity = $this->attachedConsole->getVerbosity();
        if ($consoleVerbosity <= OutputInterface::VERBOSITY_QUIET) {
            return false;
        }
        switch ($logLevel) {
            case LogLevel::DEBUG:
                return $consoleVerbosity >= OutputInterface::VERBOSITY_VERBOSE;
            default:
                return true;
        }
    }

    /**
     * Write a message to the log and to the console.
     *
     * @param string $logLevel One of the LogLevel constants
     * @param string $message
     *
     * @return $this
     *
     * @see \Psr\Log\LogLevel
     */
    protected function write($logLevel, $message)
    {
        $this->logger->log($logLevel, $message);
        if ($this->shouldWriteToConsole($logLevel)) {
            $this->attachedConsole->writeln($message);
        }

        return $this;
    }

    /**
     * Check and normalize a list of IPs.
     *
     * @param string[] $ips
     *
     * @return string[]
     */
    protected function checkIPList(array $ips)
    {
        $result = [];
        foreach ($ips as $ip) {
            $range = Factory::rangeFromString($ip);
            if ($range === null || $range instanceof Pattern) {
                $this->write(LogLevel::ERROR, t('Invalid IP address: %s', $ip));
            } else {
                $rangeString = $range->toString(false);
                if (!in_array($rangeString, $result, true)) {
                    $result[] = $rangeString;
                }
            }
        }

        return $result;
    }

    /**
     * Write out differences of IPs for a specific provider.
     *
     * @param string $handle
     * @param string[] $oldIPs
     * @param string[] $newIPs
     */
    protected function checkChangedIPs($handle, array $oldIPs, array $newIPs)
    {
        $changed = false;
        $added = array_diff($newIPs, $oldIPs);
        if (count($added) > 0) {
            $changed = true;
            $this->write(LogLevel::NOTICE, t('New IPs from proxy IP provider "%1$s": %2$s', $handle, implode(' ', $added)));
        }
        $removed = array_diff($oldIPs, $newIPs);
        if (count($removed) > 0) {
            $changed = true;
            $this->write(LogLevel::NOTICE, t('IPs no more provided by proxy IP provider "%1$s": %2$s', $handle, implode(' ', $removed)));
        }
        if ($changed === false) {
            $this->write(LogLevel::DEBUG, t('No IPs changes detected.'));
        }
    }

    /**
     * Apply the new list of trusted proxy IP addresses.
     *
     * @param string[] $newIPs
     */
    protected function applyIPs(array $newIPs)
    {
        $oldIPs = $this->config->get('concrete.security.trusted_proxies.ips');
        if (!is_array($oldIPs)) {
            $oldIPs = [];
        }
        if (array_diff($newIPs, $oldIPs) === [] && array_diff($oldIPs, $newIPs) === []) {
            $this->write(LogLevel::INFO, t('No changes in the final proxy IP list.'));

            return;
        }
        $this->config->set('concrete.security.trusted_proxies.ips', $newIPs);
        $this->config->save('concrete.security.trusted_proxies.ips', $newIPs);
        $this->write(LogLevel::NOTICE, t('Changes to the final proxy IP list have been persisted.'));
    }
}
