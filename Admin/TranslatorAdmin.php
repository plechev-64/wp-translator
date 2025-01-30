<?php

namespace Src\Module\Translator\Admin;

use Psr\Container\ContainerInterface;
use Src\Module\Translator\Admin\Metabox\MenuLanguageMetabox;
use Src\Module\Translator\Admin\Page\LanguageEditorPage;
use Src\Module\Translator\Admin\Page\Settings;
use Src\Module\Translator\Config;
use Src\Module\Translator\Enum\EntityType;
use Src\Module\Translator\Model\TranslatorEntityObject;
use Src\Module\Translator\Service\TranslatorCopyService;
use Src\Module\Translator\Service\TranslatorEntityService;
use Src\Module\Translator\Service\TranslatorStateService;

class TranslatorAdmin
{

    public function __construct(
        private readonly BarLanguageSwitcher     $barLanguageSwitcher,
        private readonly TranslatorStateService  $stateService,
        private readonly TranslatorEntityService $translatorEntityService,
        private readonly ContainerInterface      $container,
        private readonly LanguageEditorPage      $languageEditorPage,
        private readonly TranslatorCopyService   $copyService
    )
    {
    }

    public function init(): void
    {

        $this->initSettingsPage();

        if (!Config::isActive()) {
            return;
        }

        $this->stateService->changeState();

        $this->languageEditorPage->init();

        add_action('deleted_post', function (int $postId) {
            $this->translatorEntityService->deleteEntity(
                new TranslatorEntityObject($postId, EntityType::Post)
            );
        });

        add_action('delete_term', function (int $termId) {
            $this->translatorEntityService->deleteEntity(
                new TranslatorEntityObject($termId, EntityType::Tax)
            );
        });

        add_action('wp_insert_post', function (int $postId, \WP_Post $post, bool $update) {
            if (empty($_GET['trl_source']) || $update) {
                return;
            }
            $sourceId = (int)$_GET['trl_source'];
            $this->copyService->copyTemplateFromOriginal($postId, $sourceId);
            $this->copyService->copyMediaFromOriginal($postId, $sourceId);
            $this->copyService->copyTranslatedTerms($postId, $sourceId);
        }, 10, 3);

        add_action('admin_bar_menu', function () {
            $this->barLanguageSwitcher->renderAdminBarMenu();
        }, 999);

        add_action('current_screen', function () {

            $screen = get_current_screen();

            if (
                'nav-menus' === $screen->id &&
                $this->translatorEntityService->isSupportEntityType(EntityType::Tax, 'nav_menu')
            ) {

                if ($this->stateService->getState()->isChanged() && !empty($_GET['admin-bar'])) {
                    wp_redirect(admin_url('/nav-menus.php'));
                    exit;
                }

                $this->container->get(MenuLanguageMetabox::class)->init();
            }

            if (
                $screen->post_type &&
                !$screen->taxonomy &&
                $this->translatorEntityService->isSupportEntityType(EntityType::Post, $screen->post_type)
            ) {
                $this->container->get(PostPagePartManager::class)->init($screen);
            }

            if (
                $screen->taxonomy &&
                in_array($screen->base, ['edit-tags', 'term']) &&
                $this->translatorEntityService->isSupportEntityType(EntityType::Tax, $screen->taxonomy)
            ) {
                $this->container->get(TermPagePartManager::class)->init($screen);
            }

        });

    }

    private function initSettingsPage(): void
    {

        if (!function_exists('acf_add_options_page')) {
            return;
        }

        acf_add_options_page([
            'page_title' => 'Настройки мультиязычности',
            'menu_title' => 'Мультияз',
            'menu_slug' => Settings::PAGE_SLUG,
            'capability' => 'edit_posts',
            'redirect' => true,
            'autoload' => true
        ]);

        if (($_GET['page'] ?? null) === Settings::PAGE_SLUG) {
            $this->container->get(Settings::class)->init();
        }
    }

}