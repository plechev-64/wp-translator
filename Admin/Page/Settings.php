<?php

namespace Src\Module\Translator\Admin\Page;

use Src\Core\Wordpress\PostType\PostTypeManager;
use Src\Core\Wordpress\Taxonomy\TaxonomyManager;
use Src\Module\Translator\Entity\TranslatorLanguage;
use Src\Module\Translator\LocalizeMainConfig;
use Src\Module\Translator\Repository\TranslatorLangRefRepository;

class Settings {

    public const PAGE_SLUG = 'translator-settings';

    public function __construct(
        private readonly TranslatorLangRefRepository $langRefRepository,
        private readonly LocalizeMainConfig $localizeMainConfig,
        private readonly PostTypeManager $postTypeManager,
        private readonly TaxonomyManager $taxonomyManager
    ) {
    }

    public function init(): void {

        if ( ! function_exists( 'acf_add_local_field_group' ) ) {
            return;
        }

        acf_add_local_field_group(array(
            'key' => 'general',
            'title' => 'Общие настройки',
            'fields' => array (
                array (
                    'key' => 'trl_active',
                    'label' => 'Мультиязычность',
                    'name' => 'trl_active',
                    'type' => 'radio',
                    'ui' => 1,
                    'choices' => [
                        __('Неактивно', 'trl'),
                        __('Активно', 'trl')
                    ]
                ),
                array (
                    'key' => 'trl_using_languages',
                    'label' => 'Используемые языки',
                    'name' => 'trl_using_languages',
                    'type' => 'select',
                    'multiple' => 1,
                    'ui' => 1,
                    'choices' => $this->getUsingLanguageChoices()
                ),
                array (
                    'key' => 'trl_default_language',
                    'label' => 'Язык по-умолчанию',
                    'name' => 'trl_default_language',
                    'type' => 'select',
                    'ui' => 1,
                    'choices' => $this->getUsingLanguageChoices()
                )
            ),
            'location' => array (
                array (
                    array (
                        'param' => 'options_page',
                        'operator' => '==',
                        'value' => self::PAGE_SLUG,
                    ),
                ),
            ),
        ));

        acf_add_local_field_group(array(
            'key' => 'entities',
            'title' => 'Поддержка перевода',
            'fields' => array (
                array (
                    'key' => 'trl_post_types',
                    'label' => 'Типы записей',
                    'name' => 'trl_post_types',
                    'type' => 'select',
                    'multiple' => 1,
                    'ui' => 1,
                    'choices' => $this->getPostTypeChoices()
                ),
                array (
                    'key' => 'trl_taxonomies',
                    'label' => 'Таксономии',
                    'name' => 'trl_taxonomies',
                    'type' => 'select',
                    'multiple' => 1,
                    'ui' => 1,
                    'choices' => $this->getTaxonomyChoices()
                )
            ),
            'location' => array (
                array (
                    array (
                        'param' => 'options_page',
                        'operator' => '==',
                        'value' => self::PAGE_SLUG,
                    ),
                ),
            ),
        ));

        add_action('toplevel_page_translator-settings', function () {
            ?>
            <div class="postbox-container">
                <div class="postbox acf-postbox">
                    <a href="#" onclick="return fillCommand();" id="fill-command"
                       class="button button-secondary button-large">Установить дефолтный язык для сущностей без
                        перевода</a>
                    <p>При добавлении новых сущностей в поддержку перевода выполнять обязательно!</p>
                </div>
                <script>
                    function fillCommand() {
                        const TRL = <?php echo json_encode($this->localizeMainConfig->get()); ?>;
                        const formData = new FormData();
                        formData.append('_wpnonce', TRL._wpnonce);
                        fetch(TRL.restEndpoint + '/translator/fill-default', {
                            method: 'POST', body: formData,
                        })
                            .then((response) => {
                                response.json().then(function (response) {
                                    document.querySelector('#fill-command').innerHTML = 'Выполнено!';
                                });
                            })
                            .catch((error) => {
                                alert(error);
                            });
                        return false;
                    }
                </script>
            </div>
            <?php
        }, 100);

    }

    private function getTaxonomyChoices(): array
    {
        $choices = [];
        foreach ($this->taxonomyManager->getTaxonomiesObjects() as $taxonomy) {
            if ($taxonomy->translateSupport()) {
                $choices[$taxonomy->taxonomy()] = $taxonomy->args()['labels']['name'];
            }
        }

        return $choices;

    }

    private function getPostTypeChoices(): array
    {

        $choices = [
            'page' => 'Страницы'
        ];
        foreach ($this->postTypeManager->getPostTypesObjects() as $postType) {
            if ($postType->translateSupport()) {
                $choices[$postType->postType()] = $postType->args()['labels']['name'];
            }
        }

        return $choices;

    }

    private function getUsingLanguageChoices(): array {

        $languages = $this->langRefRepository->findAll();

        $choices = [];
        /** @var TranslatorLanguage $language */
        foreach($languages as $language){
            $choices[$language->getCode()] = $language->getEnglishName();
        }

        return $choices;

    }
}