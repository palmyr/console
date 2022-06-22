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

        $commandIds = [];
        foreach ($commands as $id => $tags) {
            $definition = $container->getDefinition($id);
            $definition->setPublic(true);
            $commandIds[] = $id;
        }

        $container->setParameter('command.ids', $commandIds);
    }

}