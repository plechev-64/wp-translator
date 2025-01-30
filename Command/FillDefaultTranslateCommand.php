<?php

namespace Src\Module\Translator\Command;

use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Src\Core\Transformer\Exception\TransformerException;
use Src\Core\Transformer\TransformerManager;
use JetBrains\PhpStorm\NoReturn;
use Src\Module\Translator\Entity\TranslatorEntity;
use Src\Module\Translator\Config;
use Src\Module\Translator\DTO\Model\TranslatorEntityDTO;
use Src\Module\Translator\Enum\EntityType;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:translate:fill', description: 'Установка дефолтного языка для публикаций без перевода')]
class FillDefaultTranslateCommand extends Command
{
    private string $defaultLanguage;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly TransformerManager     $transformerManager
    )
    {
        parent::__construct();
        $this->defaultLanguage = $this->getOption(Config::OPTION_DEFAULT_LANGUAGE)?? 'ru';
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

        $postTypes = $this->getOption(Config::OPTION_POST_TYPES);

        if ($postTypes) {

            foreach ($postTypes as $postType) {

                $posts = $this->entityManager->getConnection()
                    ->prepare("select p.* from wp_posts p 
                        left join wp_translator_entities t on p.\"ID\"=t.entity_id and t.entity_type='" . EntityType::Post->value . "' 
                        where t.entity_id is null 
                        and p.post_type='$postType' 
                        and p.post_status in ('publish', 'draft', 'pending')")
                    ->executeQuery()
                    ->fetchAllAssociative();
                if (!$posts) {
                    continue;
                }

                $io->comment('Заполняем переводы для публикаций типа: ' . $postType);
                $io->comment('Найдено: ' . count($posts));

                foreach ($posts as $post) {
                    $entityDto = new TranslatorEntityDTO();
                    $entityDto->entityId = $post['ID'];
                    $entityDto->entityType = EntityType::Post;
                    $entityDto->codeLang = $this->defaultLanguage;
                    $this->transformerManager->transform($entityDto, TranslatorEntity::class);
                }
            }

        }

        $taxonomies = $this->getOption(Config::OPTION_TAXONOMIES);

        if ($taxonomies) {

            foreach ($taxonomies as $taxonomy) {

                $terms = $this->entityManager->getConnection()
                    ->prepare("select tt.term_id from wp_term_taxonomy tt 
                        left join wp_translator_entities t on tt.term_id=t.entity_id and t.entity_type='" . EntityType::Tax->value . "'
                        where t.entity_id is null  
                        and tt.taxonomy='$taxonomy'")
                    ->executeQuery()
                    ->fetchAllAssociative();
                if (!$terms) {
                    continue;
                }

                $io->comment('Заполняем переводы для терминов таксономии: ' . $taxonomy);
                $io->comment('Найдено: ' . count($terms));

                foreach ($terms as $term) {
                    $entityDto = new TranslatorEntityDTO();
                    $entityDto->entityId = $term['term_id'];
                    $entityDto->entityType = EntityType::Tax;
                    $entityDto->codeLang = $this->defaultLanguage;
                    $this->transformerManager->transform($entityDto, TranslatorEntity::class);
                }
            }

        }

        $this->entityManager->flush();

        $io->success('Переводы заполнены');

        return 0;
    }

    private function getOption(string $name)
    {

        $options = $this->entityManager->getConnection()
            ->prepare("SELECT * FROM wp_options WHERE option_name='{$name}'")
            ->executeQuery()
            ->fetchAssociative();

        if (!$options) {
            return null;
        }

        $value = $options['option_value'];
        if (in_array($name, [Config::OPTION_TAXONOMIES, Config::OPTION_POST_TYPES])) {
            $value = unserialize($value);
        }

        return $value;

    }
}