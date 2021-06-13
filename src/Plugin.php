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

//require_once __DIR__ . '/../../../autoload.php';
require_once __DIR__ . '/../../../silverstripe/framework/src/includes/constants.php';

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
        $schemas = getenv('SS_GRAPHQL_COMPOSER_BUILD_SCHEMAS');
        // TODO Filter by schema

        // Not using sake because it creates a HTTPApplication through cli-script.php.
        // This would trigger middlewares assuming a HTTP request execution
        // (rather than CLI), which then connect to the database that might not be available during this build.
        $kernel = new CoreKernel(BASE_PATH, false);
        $kernel->boot();

        $this->io->write('<info>silverstripe/graphql-composer-plugin: Building schemas</info>');

        $keys = array_keys(Schema::config()->get('schemas'));
        $keys = array_filter($keys, function ($key) {
            return $key !== Schema::ALL;
        });
        foreach ($keys as $key) {
            $builder = SchemaBuilder::singleton();
            $schema = $builder->boot($key);
            $this->io->write(sprintf('Building schema "%s"', $key));
            try {
                $builder->build($schema);
            } catch (EmptySchemaException $e) {
                $this->io->write('error');
            }
        }
    }
}
