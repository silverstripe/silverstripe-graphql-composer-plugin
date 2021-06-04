<?php

namespace SilverStripe\GraphQLComposerPlugin;

use Composer\Composer;
use Composer\Plugin\PluginInterface;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Installer\InstallerEvent;
use Composer\IO\IOInterface;

class Plugin implements PluginInterface, EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return array(
            'post-install-cmd' => 'generateSchema',
            'post-update-cmd' => 'generateSchema',
        );
    }

    public function activate(Composer $composer, IOInterface $io)
    {
        // no-op
    }

    public function deactivate(Composer $composer, IOInterface $io)
    {
        // no-op
    }

    public function uninstall(Composer $composer, IOInterface $io)
    {
        // no-op
    }

    public function generateSchema(InstallerEvent $event)
    {
        var_dump($event);
    }
}
