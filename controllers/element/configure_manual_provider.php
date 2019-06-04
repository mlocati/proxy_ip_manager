<?php

namespace Concrete\Package\ProxyIpManager\Controller\Element;

use Concrete\Core\Controller\ElementController;

class ConfigureManualProvider extends ElementController
{
    /**
     * @var array
     */
    protected $configuration;

    /**
     * @param array $configuration
     */
    public function __construct(array $configuration)
    {
        parent::__construct();
        $this->configuration = $configuration;
    }

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Controller\ElementController::getElement()
     */
    public function getElement()
    {
        return 'configure_manual_provider';
    }

    public function view()
    {
        $this->set('form', $this->app->make('helper/form'));
        $this->set('ips', implode("\n", $this->configuration['ips']));
    }
}
