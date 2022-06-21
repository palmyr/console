<?php declare(strict_types=1);

namespace Palmyr\Console;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\Console\Application AS BaseApplication;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

abstract class Application extends BaseApplication
{

    private string $projectDirectory;

    private string $consoleDirectory;

    protected function __construct(string $name = 'app', string $version = '1.0.0')
    {
        parent::__construct($name, $version);
    }

    final static public function init(): void
    {
        $application = new static();

        $application->container = $containerBuilder = new ContainerBuilder();

        $containerBuilder->set('container', $containerBuilder);
        $containerBuilder->set('application', $application);
        $containerBuilder->setParameter('project_directory', $application->getprojectDirectory());
        $containerBuilder->setParameter('console_directory', $application->getConsoleDirectory());

        $application->boot(
            new YamlFileLoader($containerBuilder, new FileLocator())
        );

        $containerBuilder->compile(true);

        $application->run();
    }

    protected function getProjectDirectory(): string
    {
        if ( !isset($this->projectDirectory) ) {
            $r = new \ReflectionObject($this);

            if (!is_file($dir = $r->getFileName())) {
                throw new \LogicException(sprintf('Cannot auto-detect project dir for kernel of class "%s".', $r->name));
            }

            $dir = $rootDir = \dirname($dir);
            while (!is_file($dir.'/composer.json')) {
                if ($dir === \dirname($dir)) {
                    return $this->projectDirectory = $rootDir;
                }
                $dir = \dirname($dir);
            }
            $this->projectDirectory = $dir;
        }
        return $this->projectDirectory;
    }

    private function getConsoleDirectory(): string
    {
        if ( !isset($this->consoleDirectory) ) {
            $this->consoleDirectory = dirname(__DIR__);
        }
        return $this->consoleDirectory;
    }

    protected function boot(YamlFileLoader $loader): void
    {
        $loader->load($this->getProjectDirectory() . '/config/services.yaml');
        $loader->load($this->getConsoleDirectory() . '/config/services.yaml');
    }

}