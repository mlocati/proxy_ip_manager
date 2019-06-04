<?php

namespace ProxyIPManager\Override;

use Concrete\Controller\SinglePage\Dashboard\System\Permissions\TrustedProxies as BaseController;
use Concrete\Core\Url\Resolver\Manager\ResolverManagerInterface;

class TrustedProxiesController extends BaseController
{
    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Controller\SinglePage\Dashboard\System\Environment\Proxy::view()
     */
    public function view()
    {
        $this->addHeaderItem(<<<'EOT'
<style>
textarea#trustedIPs {
    display: none;
}
</style>
EOT
            );
        $message = json_encode(t(
            'You can manage the IP addresses <a href="%s">in this page</a>.',
            h((string) $this->app->make(ResolverManagerInterface::class)->resolve(['/dashboard/system/permissions/proxy_ip_providers']))
        ));
        $this->addFooterItem(<<<EOT
<script>
$(document).ready(function() {
    $('textarea#trustedIPs').after($('<div class="alert alert-info" />')
        .html({$message})
    );
});
</script>
EOT
        );

        return parent::view();
    }
}
