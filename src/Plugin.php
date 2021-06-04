<?php

namespace SilverStripe\GraphQLComposerPlugin;

use Composer\Composer;
use Composer\Plugin\PluginInterface;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Script\Event;
use Composer\IO\IOInterface;
use SilverStripe\GraphQL\Schema\Schema;
use SilverStripe\GraphQL\Schema\SchemaBuilder;
use SilverStripe\GraphQL\Schema\Storage\CodeGenerationStoreCreator;

class Plugin implements PluginInterface, EventSubscriberInterface
{

    /**
     * @var IOInterface
     */
    protected $io;

    public static function getSubscribedEvents()
    {
        return array(
            'post-install-cmd' => 'generateSchema',
            'post-update-cmd' => 'generateSchema',
        );
    }

    public function activate(Composer $composer, IOInterface $io)
    {
        $this->io = $io;
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

        // vendor/bin is temorarily pushed on top of PATH, see https://getcomposer.org/doc/articles/scripts.md
        $cmd = defined('SS_GRAPHQL_COMPOSER_CMD') ? SS_GRAPHQL_COMPOSER_CMD : 'sake dev/graphql/build';

        // Allow disabling by null'ing the env var
        if (!$cmd) {
            return;
        }

        // title() and section() exist in symfony/console, but not through IOInterface
        $this->io->write('<info>######################################################</info>');
        $this->io->write('<info>silverstripe/graphql-composer-plugin: Building schemas</info>');
        $this->io->write('<info>######################################################</info>');

        $out = shell_exec($cmd);
        $this->io->write($out);
    }
}
