<?php

namespace Src\Module\Translator\Command;

use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Src\Core\DTO\Model\OptionDTO;
use Src\Core\Entity\Option;
use Src\Core\Enum\Autoload;
use Src\Module\Translator\Repository\TranslatorEntityRepository;
use Src\Module\Translator\Repository\TranslatorGroupRepository;
use Src\Module\Translator\Repository\TranslatorLangRefRepository;
use JetBrains\PhpStorm\NoReturn;
use Src\Module\Translator\Entity\TranslatorEntity;
use Src\Module\Translator\Entity\TranslatorGroup;
use Src\Module\Translator\Entity\TranslatorLanguage;
use Src\Module\Translator\Config;
use Src\Module\Translator\DTO\Model\TranslatorEntityDTO;
use Src\Module\Translator\DTO\Model\TranslatorGroupDTO;
use Src\Module\Translator\Enum\EntityType;
use Src\Core\Transformer\Exception\TransformerException;
use Src\Core\Transformer\Exception\TransformerInstanceException;
use Src\Core\Transformer\Exception\TransformerNotExistException;
use Src\Core\Transformer\TransformerManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:translate:migrate', description: 'Миграция данных перевода в новые таблицы')]
class MigrateDataToTranslateCommand extends Command
{

    private const OLD_OPTION_DEFAULT_LANGUAGE = 'default_language';
    private const OLD_OPTION_USING_LANGUAGES = 'active_languages';
    private const OLD_OPTION_TAXONOMIES = 'taxonomies_sync_option';
    private const OLD_OPTION_POST_TYPES = 'custom_posts_sync_option';

    private const OLD_OPTION_TO_NEW_OPTION_MAP = [
        self::OLD_OPTION_DEFAULT_LANGUAGE => Config::OPTION_DEFAULT_LANGUAGE,
        self::OLD_OPTION_USING_LANGUAGES => Config::OPTION_USING_LANGUAGES,
        self::OLD_OPTION_TAXONOMIES => Config::OPTION_TAXONOMIES,
        self::OLD_OPTION_POST_TYPES => Config::OPTION_POST_TYPES,
    ];

    public function __construct(
        private readonly TranslatorLangRefRepository $translatorLangRefRepository,
        private readonly TranslatorGroupRepository   $translatorGroupRepository,
        private readonly TranslatorEntityRepository  $translatorEntityRepository,
        private readonly EntityManagerInterface      $entityManager,
        private readonly TransformerManager          $transformerManager
    )
    {
        parent::__construct();
    }

    /**
     * @throws Exception
     * @throws TransformerException
     */
    #[NoReturn] protected function execute(
        InputInterface  $input,
        OutputInterface $output
    ): int
    {
        $io = new SymfonyStyle($input, $output instanceof ConsoleOutputInterface ? $output->getErrorOutput() : $output);

        $this->migrateSettings($output, $io);
        $this->migrateLanguagesRef($output, $io);
        $this->migrateTranslations($output, $io);
        $this->deactivatePlugin($output, $io);

        return 0;
    }

    private function deactivatePlugin(OutputInterface $output, SymfonyStyle $io): void
    {
        $options = $this->entityManager->getConnection()
                                       ->prepare("SELECT * FROM wp_options WHERE option_name='active_plugins'")
                                       ->executeQuery()
                                       ->fetchAssociative();

        if (!$options) {
            $io->error('Не удалось получить настройки');

            return;
        }

        $options = unserialize($options['option_value']);

        $options = serialize(array_filter($options, function(string $pluginName){
            return $pluginName !== 'sitepress-multilingual-cms/sitepress.php';
        }));

        $this->entityManager->getConnection()
                            ->prepare("UPDATE wp_options SET option_value='{$options}' WHERE option_name='active_plugins'")
                            ->executeQuery();

        $io->success('Плагин деактивирован');

    }

    private function migrateSettings(OutputInterface $output, SymfonyStyle $io): void
    {

        $options = $this->entityManager->getConnection()
                                       ->prepare("SELECT * FROM wp_options WHERE option_name='icl_sitepress_settings'")
                                       ->executeQuery()
                                       ->fetchAssociative();

        if (!$options) {
            $io->error('Не удалось получить настройки');

            return;
        }

        $io->success('Начинаем миграцию настроек');

        $options = unserialize($options['option_value']);

        foreach (self::OLD_OPTION_TO_NEW_OPTION_MAP as $oldName => $newName) {
            if (isset($options[$oldName])) {
                $oldOption = $options[$oldName];

                if ($oldOption && in_array($oldName, [
                        self::OLD_OPTION_POST_TYPES,
                        self::OLD_OPTION_TAXONOMIES
                    ])) {
                    $values = [];
                    foreach ($oldOption as $item => $value) {
                        if ($value !== '0') {
                            $values[] = $item;
                        }
                    }
                    $oldOption = $values;
                }

                $optionModel = new OptionDTO();
                $optionModel->value = is_array($oldOption) ? serialize($oldOption) : $oldOption;
                $optionModel->name = $newName;
                $optionModel->autoload = Autoload::Yes;

                $this->transformerManager->transform($optionModel, Option::class);
            }
        }

        $this->entityManager->flush();

        $io->success('Миграция настроек успешно завершена');

    }

    /**
     * @throws Exception
     * @throws TransformerException
     */
    private function migrateTranslations(OutputInterface $output, SymfonyStyle $io): void
    {

        $translations = $this->entityManager->getConnection()
                                            ->prepare("SELECT * FROM wp_icl_translations WHERE element_id IS NOT NULL")
                                            ->executeQuery()
                                            ->fetchAllAssociative();

        if (!$translations) {
            $io->error('Не удалось получить переводы');

            return;
        }

        $io->success('Начинаем миграцию переводов');

        $entityTypes = EntityType::cases();
        $groups = [];
        foreach ($translations as $translation) {

            $translation['element_type'] = $this->getTypeFromOld($translation['element_type']);
            if (!$translation['element_type']) {
                continue;
            }

            $matchingTypeIndex = in_array($translation['element_type'], array_column($entityTypes, "value"));
            if ($matchingTypeIndex === false) {
                continue;
            }

            if ($translation['element_type'] === EntityType::Post->value) {
                $post = $this->entityManager->getConnection()
                                            ->prepare("SELECT * FROM wp_posts WHERE \"ID\"={$translation['element_id']}")
                                            ->executeQuery()
                                            ->fetchAllAssociative();
                if (empty($post[0])) {
                    continue;
                }
            }

            if ($translation['element_type'] === EntityType::Tax->value) {
                $termTax = $this->entityManager->getConnection()
                                               ->prepare("SELECT * FROM wp_term_taxonomy WHERE term_taxonomy_id={$translation['element_id']}")
                                               ->executeQuery()
                                               ->fetchAllAssociative();
                if (empty($termTax[0])) {
                    continue;
                }
                $translation['element_id'] = $termTax[0]['term_id'];
            }

            $groups[$translation['trid']][] = $translation;
        }
        unset($translations);

        $this->migrateGroups($groups, $output, $io);

        $this->migrateEntities($groups, $output, $io);

        $io->success('Миграция успешно завершена');

    }

    private function migrateEntities(array $groups, OutputInterface $output, SymfonyStyle $io): void {
        $io->success('Заполняем сущности');

        $progressBar = new ProgressBar($output, count($groups));
        $progressBar->start();

        foreach ($groups as $groupId => $groupTranslations) {

            $progressBar->advance();

            $group = $this->getTranslatorGroup($groupTranslations);

            if(!$group){
                continue;
            }

            foreach ($groupTranslations as $translation) {

                /** @var TranslatorEntity|null $entity */
                $entity = $this->translatorEntityRepository->findOneBy([
                    'entityType' => $translation['element_type'],
                    'entityId' => $translation['element_id']
                ]);

                if (!$entity) {
                    $this->createTranslateEntityByOldObject($translation, $group);
                }
            }
        }

        $progressBar->finish();

        $this->entityManager->flush();
    }

    private function migrateGroups(array $groups, OutputInterface $output, SymfonyStyle $io): void {

        $io->success('Создаем группы');

        $progressBar = new ProgressBar($output, count($groups));
        $progressBar->start();

        foreach ($groups as $groupId => $groupTranslations) {

            $progressBar->advance();

            try {
                $group = $this->createGroupBySource($groupTranslations);
            } catch (TransformerInstanceException|TransformerNotExistException $e) {
                $io->error('Не удалось мигрировать данные группы: ' . $groupId);
                $io->error($e->getMessage());
                continue;
            }
        }

        $this->entityManager->flush();

        $progressBar->finish();

    }

    /**
     * @throws Exception
     */
    private function migrateLanguagesRef(OutputInterface $output, SymfonyStyle $io): void
    {

        $io->success('Начинаем миграцию справочника языков');

        $ref = $this->entityManager->getConnection()
                                   ->prepare("SELECT * FROM wp_icl_languages")
                                   ->executeQuery()
                                   ->fetchAllAssociative();

        if (!$ref) {
            $io->error('Не удалось получить справочник языков');

            return;
        }

        $progressBar = new ProgressBar($output, count($ref));
        $progressBar->start();

        foreach ($ref as $r) {

            $progressBar->advance();

            $lang = $this->translatorLangRefRepository->findOneBy([
                'code' => $r['code']
            ]);

            if (!$lang) {
                $lang = new TranslatorLanguage();
            }

            $nativeName = $this->entityManager->getConnection()
                                              ->prepare("SELECT name FROM wp_icl_languages_translations WHERE language_code='{$r['code']}' AND display_language_code='{$r['code']}'")
                                              ->executeQuery()
                                              ->fetchAssociative();

            $lang->setCode($r['code']);
            $lang->setEnglishName($r['english_name']);
            $lang->setNativeName($nativeName && $nativeName['name'] ? $nativeName['name'] : null);
            $lang->setLocale($r['default_locale']);

            $this->entityManager->persist($lang);

        }

        $progressBar->finish();

        $this->entityManager->flush();

        $io->success('Миграция успешно завершена');

    }

    /**
     * @param array $oldTranslation
     * @param TranslatorGroup $group
     *
     * @return void
     * @throws TransformerException
     */
    private function createTranslateEntityByOldObject(array $oldTranslation, TranslatorGroup $group): void
    {

        $entityDto = new TranslatorEntityDTO();
        $entityDto->entityId = $oldTranslation['element_id'];
        $entityDto->entityType = match ($oldTranslation['element_type']) {
            'post' => EntityType::Post,
            'tax' => EntityType::Tax
        };
        $entityDto->codeLang = $oldTranslation['language_code'];
        $entityDto->groupId = $group->getId();

        /** @var TranslatorEntity $entity */
        $entity = $this->transformerManager->transform($entityDto, TranslatorEntity::class);

        $entity->setGroup($group);

    }

    /**
     * @throws TransformerException
     */
    private function getTranslatorGroup(array $groupTranslations): ?TranslatorGroup
    {

        $findTranslations = array_filter($groupTranslations, function ($translation) {
            return !$translation['source_language_code'];
        });

        $source = array_shift($findTranslations);

        if (!$source) {
            $source = array_shift($groupTranslations);
        }

        /** @var TranslatorGroup $group */
        $group = $this->translatorGroupRepository->findOneBy([
            'oldId' => $source['trid']
        ]);

        if ($group) {
            return $group;
        }

        return null;
    }

    private function createGroupBySource(array $groupTranslations): void {

        $findTranslations = array_filter($groupTranslations, function ($translation) {
            return !$translation['source_language_code'];
        });

        $source = array_shift($findTranslations);

        if (!$source) {
            $source = array_shift($groupTranslations);
        }

        $model = new TranslatorGroupDTO();

        $model->sourceId = $source['element_id'];
        $model->sourceType = match ($source['element_type']) {
            'post' => EntityType::Post,
            'tax' => EntityType::Tax
        };

        /** @var TranslatorGroup $group */
        $group = $this->transformerManager->transform($model, TranslatorGroup::class);

        $group->setOldId($source['trid']);
    }

    private function getTypeFromOld(string $oldType): ?string
    {

        return preg_replace('/(post|tax)(_.+)/', '${1}', $oldType);

    }
}