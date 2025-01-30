<?php

namespace Src\Module\Translator\Compatibility;

use Doctrine\Common\EventManager;
use Src\Module\ElasticSearch\Enum\Aggregator;
use Src\Module\ElasticSearch\Enum\Operator;
use Src\Module\ElasticSearch\Event\ElasticEvents;
use Src\Module\ElasticSearch\Event\EventMessage\PreSearchPostIndexMessage;
use Src\Module\ElasticSearch\Event\EventMessage\PreUpdatePostIndexMessage;
use Src\Module\ElasticSearch\Search\Filter\Condition;
use Src\Module\ElasticSearch\Search\Filter\ConditionGroup;
use Src\Module\Translator\Config;
use Src\Module\Translator\Enum\EntityType;
use Src\Module\Translator\Model\TranslatorEntityObject;
use Src\Module\Translator\Service\TranslatorEntityService;
use Src\Module\Translator\TranslatorState;
use JetBrains\PhpStorm\NoReturn;
use Psr\Container\ContainerInterface;

class ElasticEventListener
{
    private readonly EventManager $eventManager;

    public function __construct(
        private readonly ContainerInterface $container
    ) {
        $this->eventManager = $container->get(EventManager::class);
    }

    public function __invoke(): void
    {
        $this->eventManager->addEventListener(
            [
                ElasticEvents::PreUpdatePostIndex,
                ElasticEvents::PreSearchPostIndex
            ],
            $this
        );
    }

    #[NoReturn] public function PreUpdatePostIndex(PreUpdatePostIndexMessage $eventArgs): void
    {
        $index = $eventArgs->getIndex();

        $service = $this->container->get(TranslatorEntityService::class);

        $entity = $service->getEntityByObject(
            new TranslatorEntityObject($index->body->id, EntityType::Post)
        );

        $index->body->custom['language'] = $entity ? $entity->getCodeLang() : Config::getDefaultLanguage();
    }

    #[NoReturn] public function PreSearchPostIndex(PreSearchPostIndexMessage $eventArgs): void
    {
        $filter = $eventArgs->getRequest();
        $service = $this->container->get(TranslatorState::class);
        if ($service->getCurrentLanguageCode() !== Config::ALL_LANGUAGES_CODE) {
            $filter->addConditionGroup(new ConditionGroup(
                Aggregator::FILTER,
                new ConditionGroup(
                    Aggregator::MUST,
                    new Condition(Operator::EQUAL, 'custom.language', $service->getCurrentLanguageCode()),
                )
            ));
        }
    }

}