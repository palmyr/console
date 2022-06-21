<?php declare(strict_types=1);

namespace Palmyr\Console;

use Symfony\Component\Console\Application AS BaseApplication;
use Symfony\Component\DependencyInjection\ContainerBuilder;

abstract class Application extends BaseApplication
{

    private string $rootDirectory;

    protected function __construct()
    {
        parent::__construct('git-hook', '1.0.0');
    }

    final static public function init(): void
    {
        $application = new static();

        $application->container = $containerBuilder = new ContainerBuilder();

        $containerBuilder->set('container', $containerBuilder);
        $containerBuilder->set('application', $application);
        $containerBuilder->setParameter('root_directory', $application->getRootDirectory());

        $application->boot();

        $containerBuilder->compile(true);

        $application->run();
    }

    final protected function getRootDirectory(): string
    {
        if ( !isset($this->rootDirectory) ) {
            $this->rootDirectory = __DIR__;
        }
        return $this->rootDirectory;
    }

    protected function boot(): void
    {

    }

}