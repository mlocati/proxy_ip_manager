<?php

namespace Concrete\Package\ProxyIpManager;

use Concrete\Core\Backup\ContentImporter;
use Concrete\Core\Package\Package;
use ProxyIPManager\Console\UpdateProxyIPsCommand;
use ProxyIPManager\Provider\ManualProvider;
use ProxyIPManager\Provider\ProviderManager;
use ProxyIPManager\ServiceProvider;
use ProxyIPManager\Updater;

/**
 * The package controller.
 *
 * Manages the package installation, update and start-up.
 */
class Controller extends Package
{
    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Package\Package::$appVersionRequired
     */
    protected $appVersionRequired = '8.5.0';

    /**
     * The unique handle that identifies the package.
     *
     * @var string
     */
    protected $pkgHandle = 'proxy_ip_manager';

    /**
     * The package version.
     *
     * @var string
     */
    protected $pkgVersion = '0.9.1';

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Package\Package::$packageDependencies
     */
    protected $packageDependencies = [
        'cloudflare_proxy' => false,
    ];

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Package\Package::$pkgAutoloaderRegistries
     */
    protected $pkgAutoloaderRegistries = [
        'src' => 'ProxyIPManager',
    ];

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Package\Package::getPackageName()
     */
    public function getPackageName()
    {
        return t('Proxy IP Manager');
    }

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Package\Package::getPackageDescription()
     */
    public function getPackageDescription()
    {
        return t('Manage other packages that update the trusted proxy IP addresses');
    }

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Package\Package::install()
     */
    public function install()
    {
        parent::install();
        $this->installXml();
        $this->registerServiceProvider();
        $this->registerManualProvider();
    }

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Package\Package::upgrade()
     */
    public function upgrade()
    {
        parent::upgrade();
        $this->installXml();
    }

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Package\Package::uninstall()
     */
    public function uninstall()
    {
        $this->restoreManualIPs();
        parent::uninstall();
        $this->unregisterManualProvider();
    }

    /**
     * Method called when the package is initialized.
     */
    public function on_start()
    {
        $this->registerServiceProvider();
        if ($this->app->isRunThroughCommandLineInterface()) {
            $this->registerCLICommands();
        } else {
            $this->processAutomaticUpdates();
        }
    }

    /**
     * Register the service classes.
     */
    private function registerServiceProvider()
    {
        $this->app->make(ServiceProvider::class)->register();
    }

    /**
     * Install/update data from CIF file.
     */
    private function installXml()
    {
        $contentImporter = $this->app->make(ContentImporter::class);
        $contentImporter->importContentFile($this->getPackagePath() . '/config/install.xml');
    }

    /**
     * Configure and install the manual provider.
     */
    private function registerManualProvider()
    {
        $trustedIPs = $this->app->make('config')->get('concrete.security.trusted_proxies.ips');
        $configuration = [
            'ips' => is_array($trustedIPs) ? $trustedIPs : [],
        ];
        $providerManager = $this->app->make(ProviderManager::class);
        $providerManager->registerProvider('manual', ManualProvider::class, $configuration['ips'] !== []);
        $providerManager->setProviderConfiguration('manual', $configuration);
    }

    /**
     * Uninstall the manual provider.
     */
    private function restoreManualIPs()
    {
        $providerManager = $this->app->make(ProviderManager::class);
        $manualConfiguration = $providerManager->getProviderConfiguration('manual');
        $config = $this->app->make('config');
        $config->set('concrete.security.trusted_proxies.ips', $manualConfiguration['ips']);
        $config->save('concrete.security.trusted_proxies.ips', $manualConfiguration['ips']);
    }

    /**
     * Uninstall the manual provider.
     */
    private function unregisterManualProvider()
    {
        $providerManager = $this->app->make(ProviderManager::class);
        $providerManager->unregisterProvider('manual');
    }

    /**
     * Register the CLI commands provided by this package.
     */
    private function registerCLICommands()
    {
        $console = $this->app->make('console');
        $console->add(new UpdateProxyIPsCommand());
    }

    /**
     * Execute the automatic update of the proxy IP addresses (if configured so).
     */
    private function processAutomaticUpdates()
    {
        $config = $this->app->make('config');
        if (empty($config->get('proxy_ip_manager::updates.auto_updating.enabled'))) {
            return;
        }
        $lastRun = (int) $config->get('proxy_ip_manager::updates.last_run');
        if ($lastRun !== 0) {
            $lastRunAge = time() - $lastRun;
            if ($lastRunAge < (int) $config->get('proxy_ip_manager::updates.auto_updating.interval')) {
                return;
            }
        }
        $this->app->make(Updater::class)->processEnabledProviders();
    }
}
