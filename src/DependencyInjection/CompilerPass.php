<?php declare(strict_types=1);

namespace Palmyr\Console\DependencyInjection;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class CompilerPass implements CompilerPassInterface
{

    public function process(ContainerBuilder $container)
    {
        $application = $container->get('application');

        $commands = $container->findTaggedServiceIds("command");

        foreach ($commands as $id => $tags) {
            /** @var Command $command */
            $command = $container->get($id);
            $application->add($command);
        }
    }

}