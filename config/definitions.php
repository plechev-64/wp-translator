<?php

return [

    \Src\Module\Translator\LocalizeMainConfig::class => DI\factory(function(\Psr\Container\ContainerInterface $container){
        return new \Src\Module\Translator\LocalizeMainConfig(
            $container->get(\Src\Module\Translator\TranslatorState::class),
            '/' . $container->get('rest.prefix') . '/' . $container->get('rest.namespace')
        );
    }),

    \Src\Module\Translator\UrlStrategy\TranslatorUrlStrategyInterface::class => DI\get(
        \Src\Module\Translator\UrlStrategy\DirectoryUrlStrategy::class
    ),

];
