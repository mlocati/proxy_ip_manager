<?php

namespace Concrete\Package\ProxyIpManager\Controller\SinglePage\Dashboard\System\Permissions\ProxyIpProviders;

use Concrete\Core\Http\ResponseFactoryInterface;
use Concrete\Core\Page\Controller\DashboardPageController;
use Concrete\Core\Url\Resolver\Manager\ResolverManagerInterface;
use ProxyIPManager\Provider\ConfigurableProviderInterface;
use ProxyIPManager\Provider\ProviderManager;

defined('C5_EXECUTE') or die('Access Denied.');

/**
 * Controller of the /dashboard/system/permissions/proxy_ip_providers page.
 */
class Configure extends DashboardPageController
{
    /**
     * Default method called when viewing the page.
     *
     * @param mixed $handle
     */
    public function view($handle = '')
    {
        if (!is_string($handle) || $handle === '') {
            $provider = null;
        } else {
            $providerManager = $this->app->make(ProviderManager::class);
            $provider = $providerManager->getProviderByHandle($handle);
            if ($provider === null) {
                $this->flash('error', t('Unable to find the specified provider.'));
            } elseif (!$provider instanceof ConfigurableProviderInterface) {
                $this->flash('error', t('The provider "%s" is not configurable.', $provider->getName()));
                $provider = null;
            }
        }
        if ($provider === null) {
            return $this->app->make(ResponseFactoryInterface::class)->redirect(
                $this->app->make(ResolverManagerInterface::class)->resolve(['/dashboard/system/permissions/proxy_ip_providers']),
                302
            );
        }
        $this->set('resolverManager', $this->app->make(ResolverManagerInterface::class));
        $this->set('pageTitle', t('Configure %s provider', $provider->getName()));
        $this->set('handle', $handle);
        $this->set('element', $provider->getConfigurationElement($providerManager->getProviderConfiguration($handle), $this->request->getCurrentPage()));
    }

    public function save()
    {
        $post = $this->request->request;

        $handle = $post->get('handle');
        if (!is_string($handle) || $handle === '') {
            $provider = null;
        } else {
            $providerManager = $this->app->make(ProviderManager::class);
            $provider = $providerManager->getProviderByHandle($handle);
            if ($provider === null) {
                $this->flash('error', t('Unable to find the specified provider.'));
            } elseif (!$provider instanceof ConfigurableProviderInterface) {
                $this->flash('error', t('The provider "%s" is not configurable.', $provider->getName()));
                $provider = null;
            }
        }
        if ($provider === null) {
            return $this->app->make(ResponseFactoryInterface::class)->redirect(
                $this->app->make(ResolverManagerInterface::class)->resolve(['/dashboard/system/permissions/proxy_ip_providers']),
                302
            );
        }
        if (!$this->token->validate('proxy_ip_manager-configure_provider')) {
            $this->error->add($this->token->getErrorMessage());
        }
        $data = $post->all();
        $t = $this->token;
        unset($data[$t::DEFAULT_TOKEN_NAME]);
        $configuration = $provider->checkConfiguration($data, $this->error);
        if ($this->error->has()) {
            $this->view($handle);

            return;
        }
        $providerManager->setProviderConfiguration($handle, $configuration);
        $this->flash('success', t('The configuration of the provider "%s" has been saved.', $provider->getName()));

        return $this->app->make(ResponseFactoryInterface::class)->redirect(
            $this->app->make(ResolverManagerInterface::class)->resolve(['/dashboard/system/permissions/proxy_ip_providers']),
            302
        );
    }
}
