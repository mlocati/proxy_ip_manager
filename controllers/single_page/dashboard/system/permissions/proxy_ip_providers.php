<?php

namespace Concrete\Package\ProxyIpManager\Controller\SinglePage\Dashboard\System\Permissions;

use Concrete\Core\Error\UserMessageException;
use Concrete\Core\Http\ResponseFactoryInterface;
use Concrete\Core\Page\Controller\DashboardPageController;
use ProxyIPManager\Console\UpdateProxyIPsCommand;
use ProxyIPManager\Provider\ConfigurableProviderInterface;
use ProxyIPManager\Provider\ProviderInterface;
use ProxyIPManager\Provider\ProviderManager;
use ProxyIPManager\Updater;
use Punic\Comparer;
use Symfony\Component\Console\Output\BufferedOutput;

defined('C5_EXECUTE') or die('Access Denied.');

/**
 * Controller of the /dashboard/system/permissions/proxy_ip_providers page.
 */
class ProxyIpProviders extends DashboardPageController
{
    /**
     * Default method called when viewing the page.
     */
    public function view()
    {
        $config = $this->app->make('config');
        $this->requireAsset('javascript', 'vue');
        $this->addHeaderItem(<<<'EOT'
<style>
td.proxy_ip_manager-handle {
    width: 1px;
}
td.proxy_ip_manager-operations {
    width: 1px;
    text-align: right;
    white-space: nowrap;
}
td.proxy_ip_manager-enabling {
    width: 1px;
    text-align: center;
}
</style>
EOT
        );
        $providerManager = $this->app->make(ProviderManager::class);
        $providers = $providerManager->getProviders();
        $cmp = new Comparer();
        uasort($providers, function (ProviderInterface $a, ProviderInterface $b) use ($cmp) {
            return $cmp->compare($a->getName(), $b->getName());
        });
        $serializedProviders = [];
        foreach ($providers as $handle => $provider) {
            $serializedProviders[] = [
                'handle' => $handle,
                'name' => $provider->getName(),
                'enabled' => $providerManager->isProviderEnabled($handle),
                'configurable' => $provider instanceof ConfigurableProviderInterface,
            ];
        }
        $this->set('providers', $serializedProviders);
        $this->set('autoUpdatingEnabled', (bool) $config->get('proxy_ip_manager::updates.auto_updating.enabled'));
        $this->set('autoUpdatingInterval', (int) $config->get('proxy_ip_manager::updates.auto_updating.interval'));
        $cliUpdateCommand = $this->app->make(UpdateProxyIPsCommand::class);
        $this->set('cliUpdateCommandName', $cliUpdateCommand->getName());
        $this->set('nextAutomaticRun', $this->getNextAutomaticRunDescription());
    }

    public function test_provider()
    {
        if (!$this->token->validate('proxy_ip_manager-test_provider')) {
            throw new UserMessageException($this->token->getErrorMessage());
        }
        $handle = $this->request->request->get('handle');
        $providerManager = $this->app->make(ProviderManager::class);
        $provider = $providerManager->getProviderByHandle($handle);
        if ($provider === null) {
            throw new UserMessageException(t('Unable to find the provider specified.'));
        }
        $errors = $this->app->make('error');
        $ips = $provider->getProxyIPs($errors, $providerManager->getProviderConfiguration($handle));

        return $this->app->make(ResponseFactoryInterface::class)->json([
            'ips' => $ips,
            'errors' => $errors->toText(),
            'nextAutomaticRun' => $this->getNextAutomaticRunDescription(),
        ]);
    }

    public function set_provider_enabled()
    {
        if (!$this->token->validate('proxy_ip_manager-set_provider_enabled')) {
            throw new UserMessageException($this->token->getErrorMessage());
        }
        $post = $this->request->request;
        $providerManager = $this->app->make(ProviderManager::class);
        $providerManager->setProviderEnabled($post->get('handle'), !empty($post->get('enabled')));

        return $this->app->make(ResponseFactoryInterface::class)->json([
            'nextAutomaticRun' => $this->getNextAutomaticRunDescription(),
        ]);
    }

    public function enable_auto_updating()
    {
        if (!$this->token->validate('proxy_ip_manager-enable_auto_updating')) {
            throw new UserMessageException($this->token->getErrorMessage());
        }
        $enabled = !empty($this->request->request->get('enable'));
        $config = $this->app->make('config');
        $config->set('proxy_ip_manager::updates.auto_updating.enabled', $enabled);
        $config->save('proxy_ip_manager::updates.auto_updating.enabled', $enabled);

        return $this->app->make(ResponseFactoryInterface::class)->json([
            'nextAutomaticRun' => $this->getNextAutomaticRunDescription(),
        ]);
    }

    public function set_auto_updating_interval()
    {
        if (!$this->token->validate('proxy_ip_manager-set_auto_updating_interval')) {
            throw new UserMessageException($this->token->getErrorMessage());
        }
        $interval = (int) $this->request->request->get('interval');
        $config = $this->app->make('config');
        $config->set('proxy_ip_manager::updates.auto_updating.interval', $interval);
        $config->save('proxy_ip_manager::updates.auto_updating.interval', $interval);

        return $this->app->make(ResponseFactoryInterface::class)->json([
            'interval' => $interval,
            'nextAutomaticRun' => $this->getNextAutomaticRunDescription(),
        ]);
    }

    public function apply_updates()
    {
        if (!$this->token->validate('proxy_ip_manager-apply_updates')) {
            throw new UserMessageException($this->token->getErrorMessage());
        }
        $bufferedOutput = new BufferedOutput(BufferedOutput::VERBOSITY_DEBUG, false);
        $this->app->make(Updater::class)->attachConsole($bufferedOutput)->processEnabledProviders();

        $updatesResult = $this->app->make(ResponseFactoryInterface::class)->json($bufferedOutput->fetch());

        return $this->app->make(ResponseFactoryInterface::class)->json([
            'updatesResult' => $updatesResult,
            'nextAutomaticRun' => $this->getNextAutomaticRunDescription(),
        ]);
    }

    /**
     * @return string
     */
    private function getNextAutomaticRunDescription()
    {
        $config = $this->app->make('config');
        if (!$config->get('proxy_ip_manager::updates.auto_updating.enabled')) {
            return '';
        }
        $lastRun = (int) $config->get('proxy_ip_manager::updates.last_run');
        if ($lastRun === 0) {
            $nextAutomaticRun = time();
        } else {
            $nextAutomaticRun = $lastRun + (int) $config->get('proxy_ip_manager::updates.auto_updating.interval');
        }

        return t('Next automatic update execution: %s', $this->app->make('date')->formatPrettyDateTime($nextAutomaticRun, true, true));
    }
}
