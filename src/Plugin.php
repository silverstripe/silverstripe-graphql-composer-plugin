<?php

namespace SilverStripe\GraphQLComposerPlugin;

use Composer\Composer;
use Composer\Plugin\PluginInterface;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Script\Event;
use Composer\IO\IOInterface;
use SilverStripe\GraphQL\Schema\Schema;
use SilverStripe\GraphQL\Schema\SchemaBuilder;

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

    public function generateSchema(Event $event)
    {
        Schema::setVerbose(true);
        $builder = SchemaBuilder::singleton();

        $keys = array_filter(
            array_keys(Schema::config()->get('schemas')),
            function ($key) {
                return $key !== Schema::ALL;
            }
        );

        foreach ($keys as $key) {
            var_dump($key);

            // Build schema even if it exists
            $builder->buildByName($key);
        }
    }
}
