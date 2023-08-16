<?php declare(strict_types=1);

namespace Palmyr\Console\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CommandCompilerPass implements CompilerPassInterface
{

    public function process(ContainerBuilder $container): void
    {

        $commands = $container->findTaggedServiceIds("console.command");

        $commandIds = [];
        foreach ($commands as $id => $tags) {
            $definition = $container->getDefinition($id);
            $definition->setPublic(true);
            $commandIds[] = $id;
        }

        $container->setParameter('command.ids', $commandIds);

        $container->registerForAutoconfiguration(EventSubscriberInterface::class)
            ->addTag('kernel.event_subscriber');
    }

}