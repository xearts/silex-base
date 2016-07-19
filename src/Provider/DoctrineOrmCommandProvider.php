<?php
namespace Xearts\SilexBase\Provider;

use Doctrine\ORM\Tools\Console\Command;
use Knp\Console\ConsoleEvent;
use Knp\Console\ConsoleEvents;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Symfony\Component\Console\Helper\QuestionHelper;


class DoctrineOrmCommandProvider implements ServiceProviderInterface
{
    /**
     * Registers services on the given container.
     *
     * This method should only be used to configure services and parameters.
     * It should not get services.
     *
     * @param Container $app A container instance
     */
    public function register(Container $app)
    {
        $app['db.migrations.namespace'] = 'DoctrineMigrations';
        $app['db.migrations.path'] = null;
        $app['db.migrations.table_name'] = null;
        $app['db.migrations.name'] = null;

        $app['dispatcher']->addListener(ConsoleEvents::INIT, function (ConsoleEvent $event) use ($app) {
            $application = $event->getApplication();

            if (isset($app['orm.em'])) {
                $helperSet = \Doctrine\ORM\Tools\Console\ConsoleRunner::createHelperSet($app['orm.em']);

                $helperSet->set(new QuestionHelper(), 'question');

                $application->setHelperSet($helperSet);


                $commands = array(
                    new Command\SchemaTool\CreateCommand(),
                    new Command\SchemaTool\DropCommand(),
                    new Command\SchemaTool\UpdateCommand(),
                    new Command\GenerateEntitiesCommand(),
                    new Command\GenerateProxiesCommand(),
                    new Command\GenerateRepositoriesCommand(),
                    new Command\InfoCommand(),
                );

                foreach ($commands as $command) {
                    $application->add($command);
                }

            }
        });
    }

}
