<?php
namespace SilverStripe\GraphQLComposerPlugin;

use Composer\Composer;
use Composer\Plugin\PluginInterface;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Script\Event;
use Composer\IO\IOInterface;
use ReflectionClass;
use SilverStripe\Core\CoreKernel;
use SilverStripe\Core\DatabaselessKernel;
use SilverStripe\GraphQL\Schema\Exception\EmptySchemaException;
use SilverStripe\GraphQL\Schema\Schema;
use SilverStripe\GraphQL\Schema\SchemaBuilder;
use SilverStripe\ORM\Connect\MySQLDatabase;
use SilverStripe\ORM\Connect\NullDatabase;
use SilverStripe\ORM\DB;

class Plugin implements PluginInterface, EventSubscriberInterface
{

    /**
     * @var IOInterface
     */
    protected $io;

    /**
     * @var Composer
     */
    protected $composer;

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
        $this->composer = $composer;
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

        // https://github.com/composer/composer/issues/5998
        $vendorDir = $this->composer->getConfig()->get('vendor-dir');
        require_once $vendorDir . '/autoload.php';
        // Required for BASE_PATH
        require_once $vendorDir . '/silverstripe/framework/src/includes/constants.php';

        // Throw an exception when any logic in this execution is attempting to perform a query.
        // GraphQL code generation can happen on environments which don't have a valid database connection,
        // for example in CI when preparing a deployment package.
        $db = new NullDatabase();
        $db->setQueryErrorMessage('Database query detected during GraphQL code generation: %s');
        $db->setErrorMessage('Database activity detected during GraphQL code generation.');
        DB::set_conn($db);

        // Not using sake because it creates a HTTPApplication through cli-script.php.
        // This would trigger middlewares assuming a HTTP request execution
        // (rather than CLI), which then connect to the database that might not be available during this build.
        $kernel = new DatabaselessKernel(BASE_PATH);

        // Not booting error handling since it assumes a (faked) HTTP execution context
        // through SilverStripe\Logging\HTTPOutputHandler, and can in some contexts fail
        // because HTTP variables aren't defined (see cli-script.php).
        $kernel->setBootErrorHandling(false);

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
