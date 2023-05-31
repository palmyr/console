<?php declare(strict_types=1);

namespace Palmyr\Console;

use Palmyr\Console\DependencyInjection\CommandCompilerPass;
use Palmyr\Console\DependencyInjection\ConsoleExtension;
use Palmyr\SymfonyCommonUtils\DependencyInjection\SymfonyCommonUtilsExtension;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Console\Application AS BaseApplication;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\Compiler\RegisterServiceSubscribersPass;
use Symfony\Component\DependencyInjection\Compiler\ResolveServiceSubscribersPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\ErrorHandler\Debug;
use Symfony\Component\EventDispatcher\DependencyInjection\RegisterListenersPass;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

abstract class Application extends BaseApplication
{

    private string $projectDirectory;

    private string $consoleDirectory;

    protected ContainerBuilder $container;

    protected FileLocator $fileLocator;

    protected function __construct(string $env, string $name = 'app', string $version = '1.1.1')
    {
        parent::__construct($name, $version);
        $inputDefinition = $this->getDefinition();
        $inputDefinition->addOption(new InputOption('--env', '-e', InputOption::VALUE_REQUIRED, 'The Environment name.', $env));
    }

    final static public function init(string $projectDirectory): void
    {

        $input = new ArgvInput();

        if (null !== $env = $input->getParameterOption(['--env', '-e'], null, true)) {
            putenv("APP_ENV".'='.$env);
            $_SERVER["APP_ENV"] = $env;
        }

        (new \Symfony\Component\Dotenv\Dotenv())->bootEnv(path: $projectDirectory . DIRECTORY_SEPARATOR . ".env");

        $application = new static($_SERVER["APP_ENV"]);
        Debug::enable();

        $application->boot();
        $application->run(input: $input);
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

    private function boot(): void
    {

        $this->container = $containerBuilder = new ContainerBuilder();
        $this->fileLocator = $fileLocator = new FileLocator($this->getProjectDirectory());

        $containerBuilder->set('container', $containerBuilder);
        $containerBuilder->set('application', $this);
        $containerBuilder->set('file_locator', $fileLocator);

        $containerBuilder->setParameter('application_directory', $this->getprojectDirectory());

        $containerBuilder->registerForAutoconfiguration(Command::class)->addTag('console.command');
        $containerBuilder->registerForAutoconfiguration(EventSubscriberInterface::class)->addTag('kernel.event_subscriber');
        $containerBuilder->registerForAutoconfiguration(ServiceSubscriberInterface::class)->addTag('container.service_subscriber');

        foreach ($this->getCompilerPasses() as $type => $compilerPasses ) {
            foreach ( $compilerPasses as $compilerPass ) {
                $containerBuilder->addCompilerPass($compilerPass, $type);
            }
        }

        foreach ( $this->getExtensions() as $extension ) {
            $containerBuilder->registerExtension($extension);
            $containerBuilder->loadFromExtension($extension->getAlias());
        }

        $this->loadExtras($containerBuilder);

        $containerBuilder->compile(true);
    }

    protected function getCompilerPasses(): array
    {
        return [
            PassConfig::TYPE_OPTIMIZE => [
                new CommandCompilerPass(),
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

    protected function getExtensions(): array
    {
        return [
            new ConsoleExtension(),
            new SymfonyCommonUtilsExtension(),
        ];
    }

    protected function loadExtras(ContainerBuilder $containerBuilder): void
    {
        $loader = new YamlFileLoader($containerBuilder, $this->fileLocator);

        $loader->load("config/services.yaml");
    }

}