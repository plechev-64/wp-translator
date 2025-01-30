<?php

namespace Src\Module\Translator\UrlStrategy;

use Src\Module\Translator\Entity\TranslatorEntity;
use Src\Module\Translator\Config;
use Src\Module\Translator\Enum\EntityType;
use Src\Module\Translator\Model\TranslatorEntityObject;
use Src\Module\Translator\Service\TranslatorEntityService;
use Src\Module\Translator\TranslatorState;

class DirectoryUrlStrategy implements TranslatorUrlStrategyInterface
{

    public function __construct(
        private readonly TranslatorEntityService $translatorEntityService,
        private readonly TranslatorState $state
    ) {
    }

    public function init(): void
    {
        add_filter('init', function () {
            $this->rewriteRule();
        }, 10);

        add_filter('post_type_link', function (string $link, \WP_Post $post) {
            return $this->permalinkPostFilter($link, $post);
        }, 1, 2);

        add_filter('page_link', function (string $link, int $pageId) {
            return $this->permalinkPostFilter($link, get_post($pageId));
        }, 1, 2);

        add_filter('term_link', function (string $link, \WP_Term $term) {
            return $this->permalinkTermFilter($link, $term);
        }, 1, 2);

        add_action('pre_get_posts', function ($query) {
            $this->setCurrentPageObjectByLanguage($query);
        });

    }

    public function transformUrl(string $url): string
    {
        $currentLanguage = $this->state->getCurrentLanguage();
        if ($currentLanguage->code !== Config::getDefaultLanguage()) {
            $url = $this->filterLink($url, $currentLanguage->code);
        }

        return $url;
    }

    private function setCurrentPageObjectByLanguage($query): void
    {

        $isFrontPage = ($query->get('is_front_page') && $query->get('language')) || ( ! $query->query && $query->is_page);
        $isSingular  = (($query->queried_object_id || ! empty($query->get('post_type'))) && $query->is_singular);

        if (
            $query->is_main_query() &&
            (
                $isFrontPage || $isSingular
            )
        ) {

            if ( ! $query->get('language')) {
                $query->set('language', Config::getDefaultLanguage());
            }

            if ($isFrontPage) {
                $query->set('is_front_page', 1);
                $query->is_home = 1;
                $pageId         = get_option('page_on_front');

                if ( ! $pageId) {
                    return;
                }

                add_filter('body_class', function (array $classes) {
                    $classes[] = 'home';
                    $classes[] = 'page-main';

                    return $classes;
                });

                $entities = $this->state->getHomePages();

            } else {

                $postType = $query->queried_object_id ? $query->queried_object->post_type : $query->get('post_type');
                if ($query->queried_object_id && ! $this->translatorEntityService->isSupportEntityType(
                        EntityType::Post,
                        $postType
                    )) {
                    return;
                }

                $pageId = $query->queried_object_id ?? get_page_by_path($query->query['name'], OBJECT, $postType)?->ID;

                if ( ! $pageId) {
                    return;
                }

                $entities = $this->translatorEntityService->getTranslateEntitiesByPostObject(
                    new TranslatorEntityObject($pageId, EntityType::Post), $postType
                );
            }

            if ( ! $entities) {
                return;
            }

            /** @var TranslatorEntity $entity */
            foreach ($entities as $entity) {
                if ($entity->getCodeLang() === $query->get('language')) {
                    $post = get_post($entity->getEntityId());
                    $query->set('p', $post->ID);
                    $query->set('pagename', $post->post_name);
                    $query->queried_object    = $post;
                    $query->queried_object_id = $post->ID;
                    break;
                }
            }
        }

    }

    public function permalinkTermFilter(string $url, \WP_Term $term): string
    {
        $currentLanguage = $this->state->getCurrentLanguage();
        if ($currentLanguage->code !== Config::getDefaultLanguage() && $this->translatorEntityService->isSupportEntityType(EntityType::Tax,
                $term->taxonomy)) {
            $url = $this->filterLink($url, $currentLanguage->code);
        }

        return $url;
    }

    public function permalinkPostFilter(string $url, \WP_Post $post): string
    {

        $currentLanguage = $this->state->getCurrentLanguage();
        if ($currentLanguage->code !== Config::getDefaultLanguage() && $this->translatorEntityService->isSupportEntityType(EntityType::Post,
                $post->post_type)) {

            if (in_array($post->ID, $this->state->getHomePageIds())) {
                $url = get_home_url();
            }

            $url = $this->filterLink($url, $currentLanguage->code);
        }

        return $url;
    }

    private function filterLink(string $url, string $langCode): string
    {
        $homeUrl = get_home_url();

        if (str_contains($url, $homeUrl)) {
            $url = str_replace($homeUrl, $homeUrl . '/' . $langCode, $url);
        } else {
            $url = $homeUrl . '/' . $langCode . '/' . preg_replace('/^\//', '', $url);
        }

        return $url;
    }

    private function rewriteRule(): void
    {

        $languagesExceptDefault = array_filter(Config::getUsingLanguages(), function ($language) {
            return $language !== Config::getDefaultLanguage();
        });

        $postTypesWithOutPage = array_filter(Config::getPostTypes(), function ($postType) {
            return $postType !== 'page';
        });

        //таксономии постраничная навигация
        add_rewrite_rule('(' . implode('|', $languagesExceptDefault) . ')/(' . implode('|',
                Config::getTaxonomies()) . ')/([^/]+)/page/?([0-9]{1,})/?$',
            'index.php?language=$matches[1]&$matches[2]=$matches[3]&page=$matches[4]', 'top');
        //таксономии главная страница
        add_rewrite_rule('(' . implode('|', $languagesExceptDefault) . ')/(' . implode('|',
                Config::getTaxonomies()) . ')/([^/]+)/?$', 'index.php?language=$matches[1]&$matches[2]=$matches[3]',
            'top');
        //типы записей
        add_rewrite_rule('(' . implode('|', $languagesExceptDefault) . ')/(' . implode('|',
                $postTypesWithOutPage) . ')/([^/]+)(?:/([0-9]+))?/?$',
            'index.php?language=$matches[1]&$matches[2]=$matches[3]&page=$matches[4]', 'top');
        //дочерние страницы произвольных типов записей
        add_rewrite_rule('(' . implode('|', $languagesExceptDefault) . ')/(' . implode('|',
                $postTypesWithOutPage) . ')/(.?.+?)(?:/([0-9]+))?/?$',
            'index.php?language=$matches[1]&$matches[2]=$matches[3]&page=$matches[4]', 'top');
        //страницы
        add_rewrite_rule('(' . implode('|', $languagesExceptDefault) . ')/([^/]+)(?:/([0-9]+))?/?$',
            'index.php?language=$matches[1]&pagename=$matches[2]&page=$matches[3]', 'top');
        //дочерние страницы
        add_rewrite_rule('(' . implode('|', $languagesExceptDefault) . ')/(.?.+?)(?:/([0-9]+))?/?$',
            'index.php?language=$matches[1]&pagename=$matches[2]&page=$matches[3]', 'top');

        //главная страница
        $frontPageId = get_option('page_on_front');
        if ($frontPageId) {
            $frontPage = get_post($frontPageId);
            add_rewrite_rule('(' . implode('|', $languagesExceptDefault) . ')/?$',
                'index.php?language=$matches[1]&is_front_page=1&pagename=' . $frontPage->post_name, 'top');
        }

        add_rewrite_tag('%language%', '([^&]+)');
        add_rewrite_tag('%is_front_page%', '([^&]+)');

        add_filter('query_vars', function ($vars) {
            $vars[] = 'language';
            $vars[] = 'is_front_page';

            return $vars;
        });

    }

}