<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

return function (ContainerConfigurator $configurator) {
    $src = dirname(__DIR__).'/src';

    $services = $configurator->services();
    $services->defaults()->autowire(true)->autoconfigure(true)->public(false);

    $services->load('Base\\Classroom\\', $src.'/')
        ->exclude([$src.'/DependencyInjection/', $src.'/Entity/', $src.'/Enum/', $src.'/Controller/Admin/', $src.'/Admin/', $src.'/EventSubscriber/', $src.'/ClassroomBundle.php']);

    $services->load('Base\\Classroom\\Controller\\Client\\', $src.'/Controller/Client/')
        // The shop sells through omnibase/marketplace's quick order.
        ->exclude(class_exists('Base\\Marketplace\\Service\\QuickOrder') ? [] : [$src.'/Controller/Client/ShopController.php'])
        ->tag('controller.service_arguments');

    if (class_exists('Base\\Admin\\Controller\\AbstractCrudController')) {
        $services->load('Base\\Classroom\\Controller\\Admin\\', $src.'/Controller/Admin/')
            ->exclude(class_exists('Base\\Marketplace\\Entity\\Product') ? [] : [$src.'/Controller/Admin/Crud/ResourceOfferCrudController.php'])
            ->tag('controller.service_arguments');
        $services->load('Base\\Classroom\\Admin\\', $src.'/Admin/');
    }

    // The shop's side, only with omnibase/marketplace: a paid resource delivered.
    if (class_exists('Base\\Marketplace\\Event\\OrderPaidEvent')) {
        $services->load('Base\\Classroom\\EventSubscriber\\', $src.'/EventSubscriber/')
            ->exclude(class_exists('Base\\Marketplace\\Event\\QuickOrderDoneEvent') ? [] : [$src.'/EventSubscriber/QuickOrderFilesListener.php']);
    }
};
