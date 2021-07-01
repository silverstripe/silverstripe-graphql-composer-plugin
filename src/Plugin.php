<?php
namespace SilverStripe\GraphQLComposerPlugin;

use Composer\Composer;
use Composer\Plugin\PluginInterface;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Script\Event;
use Composer\IO\IOInterface;
use SilverStripe\Core\CoreKernel;
use SilverStripe\GraphQL\Schema\Exception\EmptySchemaException;
use SilverStripe\GraphQL\Schema\Schema;
use SilverStripe\GraphQL\Schema\SchemaBuilder;

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

        // https://github.com/composer/composer/issues/5998
        $vendorDir = $composer->getConfig()->get('vendor-dir');
        require_once $vendorDir . '/autoload.php';
        // Required for BASE_PATH
        require_once $vendorDir . '/silverstripe/framework/src/includes/constants.php';
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
        $schemas = getenv('SS_GRAPHQL_COMPOSER_BUILD_SCHEMAS');

        // Allow opt-out
        if ($schemas === '') {
            return;
        }

        // Not using sake because it creates a HTTPApplication through cli-script.php.
        // This would trigger middlewares assuming a HTTP request execution
        // (rather than CLI), which then connect to the database that might not be available during this build.
        $kernel = new CoreKernel(BASE_PATH, false);
        try {
            // Any composer update can introduce new config statements that require a manifest flush.
            // Since there is no way to pass flush=1 through composer commands, the safest way is to always perform the flush.
            $kernel->boot(true);

            $this->io->write('<info>silverstripe/graphql-composer-plugin: Building schemas</info>');

            $keys = $schemas ? explode(',', $schemas) : array_keys(Schema::config()->get('schemas'));

            $keys = array_filter($keys, function ($key) {
                return $key !== Schema::ALL;
            });
            foreach ($keys as $key) {
                $builder = SchemaBuilder::singleton();
                $schema = $builder->boot($key);
                $this->io->write(sprintf('Building GraphQL schema "%s"... ', $key), false);
                try {
                    // Not invoking with clean=1 since this would negatively affect runtime of "composer install"
                    $builder->build($schema, true);
                    $this->io->write('done.');
                } catch (EmptySchemaException $e) {
                    $this->io->write('schema is empty, skipping.');
                }
            }
        } finally {
            $kernel->shutdown();
        }
    }
}
