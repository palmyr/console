<?php declare(strict_types=1);

namespace Palmyr\Console\DependencyInjection;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CompilerPass implements CompilerPassInterface
{

    public function process(ContainerBuilder $container)
    {

        $commands = $container->findTaggedServiceIds("command");

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