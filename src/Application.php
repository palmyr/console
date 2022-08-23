<?php declare(strict_types=1);

namespace Palmyr\Console;

use Palmyr\Console\DependencyInjection\CompilerPass;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Console\Application AS BaseApplication;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\Compiler\RegisterServiceSubscribersPass;
use Symfony\Component\DependencyInjection\Compiler\ResolveServiceSubscribersPass;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\EventDispatcher\DependencyInjection\RegisterListenersPass;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

abstract class Application extends BaseApplication
{

    private string $projectDirectory;

    private string $consoleDirectory;

    protected ContainerBuilder $container;

    protected FileLocator $fileLocator;

    protected function __construct(string $name = 'app', string $version = '1.1.1')
    {
        parent::__construct($name, $version);
    }

    final static public function init(): void
    {
        $application = new static();

        $application->container = $containerBuilder = new ContainerBuilder();
        $application->fileLocator = $fileLocator = new FileLocator();

        $containerBuilder->set('container', $containerBuilder);
        $containerBuilder->set('application', $application);
        $containerBuilder->set('file_locator', $fileLocator);
        $containerBuilder->setParameter('project_directory', $application->getprojectDirectory());
        $containerBuilder->setParameter('console_directory', $application->getConsoleDirectory());

        $application->boot(
            $containerBuilder,
            new YamlFileLoader($containerBuilder, $fileLocator)
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

    protected function boot(ContainerBuilder $container, YamlFileLoader $loader): void
    {
        $container->registerForAutoconfiguration(Command::class)->addTag('command');
        $container->registerForAutoconfiguration(EventSubscriberInterface::class)->addTag('kernel.event_subscriber');
        $container->registerForAutoconfiguration(ServiceSubscriberInterface::class)->addTag('container.service_subscriber');

        foreach ($this->getCompilerPasses() as $type => $compilerPasses ) {
            foreach ( $compilerPasses as $compilerPass ) {
                $container->addCompilerPass($compilerPass, $type);
            }
        }

        $loader->load($this->getProjectDirectory() . '/config/services.yaml');
        $loader->load($this->getConsoleDirectory() . '/config/services.yaml');
    }

    public function doRun(InputInterface $input, OutputInterface $output)
    {

        $this->setDispatcher($this->container->get("event_dispatcher"));

        $commands = $this->container->getParameter('command.ids');

        foreach ($commands as $id) {
            /** @var Command $command */
            $command = $this->container->get($id);
            $this->add($command);
        }

        return parent::doRun($input, $output);
    }

    protected function getCompilerPasses(): array
    {
        return [
            PassConfig::TYPE_OPTIMIZE => [
                new CompilerPass(),
                new RegisterServiceSubscribersPass(),
                new ResolveServiceSubscribersPass(),
            ],
            PassConfig::TYPE_BEFORE_OPTIMIZATION => [],
            PassConfig::TYPE_BEFORE_REMOVING => [
                new RegisterListenersPass(),
            ],
            PassConfig::TYPE_REMOVE => [],
            PassConfig::TYPE_AFTER_REMOVING => [],
        ];
    }

}