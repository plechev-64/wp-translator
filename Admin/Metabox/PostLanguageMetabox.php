<?php

namespace Src\Module\Translator\Admin\Metabox;

use Doctrine\ORM\EntityManagerInterface;
use Src\Module\Translator\Entity\TranslatorEntity;
use Src\Module\Translator\Entity\TranslatorLanguage;
use Src\Module\Translator\Admin\ManageLink\Post\PostLinkManager;
use Src\Module\Translator\Admin\ManageLink\Post\PostLinkManagerIncomeObject;
use Src\Module\Translator\Admin\ManageLink\Post\TranslatePostManageLink;
use Src\Module\Translator\Config;
use Src\Module\Translator\Enum\EntityType;
use Src\Module\Translator\Model\TranslatorEntityObject;
use Src\Module\Translator\Service\TranslatorEntityService;
use Src\Module\Translator\Service\TranslatorLanguageService;
use Src\Core\Transformer\TransformerManager;
use Src\Module\Translator\TranslatorState;
use WP_Post;

class PostLanguageMetabox extends AbstractLanguageMetabox
{

    public function __construct(
        private readonly TranslatorState $state,
        private readonly PostLinkManager $postLinkManager,
        private readonly TranslatorEntityService $translatorEntityService,
        private readonly TranslatorLanguageService $translatorLanguageService,
        TransformerManager $transformerManager,
        EntityManagerInterface $entityManager
    ) {
        parent::__construct(
            $translatorEntityService,
            $transformerManager,
            $entityManager
        );
    }

    public function getEntityType(): EntityType
    {
        return EntityType::Post;
    }

    public function getProps(): PropsMetabox
    {
        $props            = new PropsMetabox('trl-language', __('Язык публикации', 'trl'));
        $props->postTypes = Config::getPostTypes();
        $props->context   = 'side';

        return $props;
    }

    /**
     * @param WP_Post $item
     *
     * @return string
     */
    public function getContent(mixed $item): string
    {

        $entityLangCode = $_GET['lang'] ?? $this->state->getCurrentLanguageCode();
        $sourceId       = (int)($_GET['trl_source'] ?? 0);
        $entityId       = $sourceId ?: $item->ID;
        $group          = null;

        $translateEntities = $this->translatorEntityService->getTranslateEntitiesByPostObject(
            new TranslatorEntityObject($entityId, $this->getEntityType()), get_post($entityId)->post_type
        );

        if ($translateEntities) {
            /** @var TranslatorEntity $translateEntity */
            foreach ($translateEntities as $translateEntity) {
                if ($translateEntity->getEntityId() === $entityId) {
                    if ( ! $sourceId) {
                        $entityLangCode = $translateEntity->getCodeLang();
                    }
                    $group = $translateEntity->getGroup();
                    break;
                }
            }
        }

        ob_start();

        ?>
        <style>
            .field {
                margin: 15px 0;
            }

            .field-other-languages {
                border: 1px solid #ccc;
                padding: 0;
            }

            .row-language {
                display: flex;
                justify-content: space-between;
                border-bottom: 1px solid #ccc;
                padding: 5px;
            }

            .row-language a {
                text-decoration: none;
            }

            .field-other-languages .hidden {
                display: none;
            }
        </style>
        <input type="hidden" name="trl_group_id" value="<?php echo $group ? $group->getId() : 0 ?>">
        <div class="field field-current-language">
            <label>
                Текущий язык
                <select name="trl_code">
                    <?php
                    /** @var TranslatorLanguage $language */
                    foreach ($this->translatorLanguageService->getUsingLanguages() as $language):
                        if ($entityLangCode !== $language->getCode()) {
                            continue;
                        }

                        ?>
                        <option value="<?php echo $language->getCode() ?>" <?php selected($language->getCode(),
                            $entityLangCode); ?>><?php echo $language->getEnglishName() ?></option>
                    <?php
                    endforeach;
                    ?>
                </select>
            </label>
        </div>
        <?php if ($item): ?>
        <div class="field field-other-languages <?php if ( ! $translateEntities) {
            echo 'hidden';
        } ?>">
            <?php


            $links = $this->postLinkManager->getLinks(
                new PostLinkManagerIncomeObject($sourceId ? get_post($sourceId) : $item, $translateEntities)
            );

            /** @var TranslatorLanguage $language */
            foreach ($this->translatorLanguageService->getUsingLanguages() as $language):
                if ($language->getCode() === $entityLangCode) {
                    continue;
                }
                ?>
                <div class="row-language">
                    <span><?php echo $language->getEnglishName(); ?></span>
                    <?php
                    /** @var TranslatePostManageLink $link */
                    foreach ($links as $link):
                        if ($link->langCode !== $language->getCode()) {
                            continue;
                        }
                        ?>
                        <a href="<?php echo $link->getUrl(); ?>">
                            <i class="dashicons <?php echo($link->isCurrent() ? 'dashicons-edit' : 'dashicons-plus-alt2'); ?>"></i>
                        </a>
                    <?php
                    endforeach;
                    ?>
                </div>
            <?php
            endforeach;
            ?>
        </div>
    <?php
    endif;

        return ob_get_clean();
    }

}
