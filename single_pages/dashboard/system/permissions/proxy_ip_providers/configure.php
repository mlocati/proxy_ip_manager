<?php

defined('C5_EXECUTE') or die('Access Denied.');

/*
 * @var bool $_bookmarked
 * @var Concrete\Core\Page\Page $c
 * @var Concrete\Package\ProxyIpManager\Controller\SinglePage\Dashboard\System\Permissions\ProxyIpProviders\Configure $controller
 * @var Concrete\Core\Application\Service\Dashboard $dashboard
 * @var Concrete\Core\Error\ErrorList\ErrorList $error
 * @var Concrete\Core\Form\Service\Form $form
 * @var bool $hideDashboardPanel
 * @var Concrete\Core\Html\Service\Html $html
 * @var Concrete\Core\Application\Service\UserInterface $interface
 * @var string $pageTitle
 * @var array $scopeItems
 * @var bool $showPrivacyPolicyNotice
 * @var Concrete\Theme\Dashboard\PageTheme $theme
 * @var Concrete\Core\Page\View\PageView $this
 * @var Concrete\Core\Validation\CSRF\Token $token
 * @var Concrete\Core\Page\View\PageView $view
 *
 * @var Concrete\Core\Url\Resolver\Manager\ResolverManagerInterface $resolverManager
 * @var string $handle
 * @var Concrete\Core\Filesystem\Element $element
 */

?>
<form method="POST" action="<?= $view->action('save') ?>">
    <?php $token->output('proxy_ip_manager-configure_provider') ?>
    <input type="hidden" name="handle" value="<?= h($handle) ?>" />

    <?php $element->render() ?>

    <div class="ccm-dashboard-form-actions-wrapper">
        <div class="ccm-dashboard-form-actions">
            <div class="pull-right">
                <a href="<?= $resolverManager->resolve(['/dashboard/system/permissions/proxy_ip_providers/']) ?>" class="btn btn-default"><?= t('Cancel') ?></a>
                <input type="submit" class="btn btn-primary" value="<?= t('Save') ?>" />
            </div>
        </div>
    </div>
</form>
