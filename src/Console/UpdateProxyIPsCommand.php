<?php

namespace ProxyIPManager\Console;

use Concrete\Core\Console\Command;
use ProxyIPManager\Updater;

class UpdateProxyIPsCommand extends Command
{
    public function handle(Updater $updater)
    {
        $updater->attachConsole($this->output);
        $updater->processEnabledProviders();
    }

    /**
     * {@inheritdoc}
     *
     * @see \Symfony\Component\Console\Command\Command::configure()
     */
    protected function configure()
    {
        self::ALLOWASROOT_ENV;
        $this
            ->setName('pim:update')
            ->setDescription('Update the list of trusted proxy IP addresses.')
        ;
        $this->setCanRunAsRoot(false);
    }
}
