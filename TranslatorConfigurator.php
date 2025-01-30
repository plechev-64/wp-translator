<?php

namespace Src\Module\Translator;

use Src\Core\Container\ContainerBuilder;
use Src\Core\Container\ContainerConfigurator;
use Src\Module\Translator\Command\FillDefaultTranslateCommand;
use Src\Module\Translator\Command\MigrateDataToTranslateCommand;
use Src\Module\Translator\Command\TranslatorInstallCommand;
use Src\Module\Translator\Compatibility\ElasticEventListener;
use Src\Module\Translator\DTO\Model\TranslatorLanguageDTO;
use Src\Module\Translator\DTO\Transformer\TranslatorEntityFromModelDTOTransformer;
use Src\Module\Translator\DTO\Transformer\TranslatorGroupFromModelDTOTransformer;
use Src\Module\Translator\DTO\Transformer\TranslatorLanguageDTOFromEntityTransformer;
use Src\Module\Translator\DTO\Transformer\TranslatorLanguageFromModelTransformer;
use Src\Module\Translator\Entity\TranslatorEntity;
use Src\Module\Translator\Entity\TranslatorGroup;
use Src\Module\Translator\Entity\TranslatorLanguage;

class TranslatorConfigurator implements ContainerConfigurator
{

    public static function configure(ContainerBuilder $containerBuilder): void
    {

        $containerBuilder->addDefinitions(
            include __DIR__ . '/config/definitions.php'
        );

        $containerBuilder->addController(TranslatorController::class);

        $containerBuilder->addTransformer(
            TranslatorLanguage::class,
            TranslatorLanguageFromModelTransformer::class
        );
        $containerBuilder->addTransformer(
            TranslatorEntity::class,
            TranslatorEntityFromModelDTOTransformer::class
        );
        $containerBuilder->addTransformer(
            TranslatorGroup::class,
            TranslatorGroupFromModelDTOTransformer::class
        );
        $containerBuilder->addTransformer(
            TranslatorLanguageDTO::class,
            TranslatorLanguageDTOFromEntityTransformer::class
        );

        $containerBuilder->addCommand(FillDefaultTranslateCommand::class);
        $containerBuilder->addCommand(MigrateDataToTranslateCommand::class);
        $containerBuilder->addCommand(TranslatorInstallCommand::class);

        $containerBuilder->addAssetDir(__DIR__ . '/assets', 'translator');

        $containerBuilder->addEntityPath(__DIR__ . '/Entity');

        $containerBuilder->onWpReady(Translator::class);
        $containerBuilder->onAppReady(ElasticEventListener::class);
    }
}