<?php

namespace Src\Module\Translator;

use Src\Module\Translator\Service\TextdomainRegistry;
use Src\Module\Translator\Service\TranslatorOptionsManager;
use Src\Module\Translator\Service\TranslatorPostService;
use Src\Module\Translator\Service\TranslatorTaxService;
use Src\Module\Translator\UrlStrategy\TranslatorUrlStrategyInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Src\Module\Translator\Admin\TranslatorAdmin;
use Src\Module\Translator\Compatibility\DuplicatePostPlugin;
use Src\Module\Translator\Service\TranslatorStateService;
use Src\Core\Transformer\Exception\TransformerException;

class Translator
{

    public function __construct(
        private readonly TranslatorStateService $translatorService,
        private readonly ContainerInterface $container,
        private readonly TranslatorPostService $postService,
        private readonly TranslatorTaxService $taxService,
    ) {
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws TransformerException
     */
    public function __invoke(): void
    {

        Config::init();

        add_action('_admin_menu', function () {
            $this->container->get(TranslatorAdmin::class)->init();
        });

        if ( ! Config::isActive()) {
            return;
        }

        /**
         * подменяем дефолтный поиск файлов локализации на свой
         */
        global $wp_textdomain_registry;
        $wp_textdomain_registry = new TextdomainRegistry();

        $this->translatorService->initState();

        /**
         * Отключаем перевод текста для админки
         */
        if(!is_admin()){
            add_action('after_setup_theme', function () {
                add_filter('pre_determine_locale', function () {
                    return $this->translatorService->getState()->getCurrentLanguage()->locale;
                });
            });
        }

        add_action('parse_request', function () {
            if ( ! empty($GLOBALS['wp']->query_vars['rest_route'])) {
                $this->translatorService->changeState();
            }
        }, 1);

        add_action('wp_loaded', function () {
            $this->container->get(TranslatorFilterSql::class)->init();
            $this->container->get(TranslatorOptionsManager::class)->initHooks();
        });

        add_action('parse_query', function ($query) {
            if ($query->is_main_query() && ! did_action('admin_menu')) {
                /**
                 * работа с фронтом
                 */
                $this->container->get(TranslatorFront::class)->init();
                add_action('wp', function () {
                    $this->reloadTranslation();
                });
            }else if ( ! empty($GLOBALS['wp']->query_vars['rest_route'])) {
                /**
                 * работа с рест-запросами
                 */
                $this->translatorService->changeState();
                $this->reloadTranslation();
            }
        }, 1);

        $this->container->get(TranslatorUrlStrategyInterface::class)->init();

        if (wp_doing_ajax()) {
            $this->container->get(TranslatorAjaxLoader::class)->init();
        }

        $this->postService->initHooks();
        $this->taxService->initHooks();

        $this->compatibility();

    }

    private function compatibility(): void
    {
        if (defined('DUPLICATE_POST_CURRENT_VERSION')) {
            $this->container->get(DuplicatePostPlugin::class)->init();
        }
    }

    private function reloadTranslation(): void {
        global $wp_locale;

        if ($this->translatorService->getState()->isChanged()) {
            $locale = $this->translatorService->getState()->getCurrentLanguage()->locale;
            $this->reloadTranslationByLocale($locale);
            \WP_Translation_Controller::get_instance()->set_locale($locale);
        }

        $wp_locale              = new \WP_Locale();
    }

    private function reloadTranslationByLocale($locale)
    {
        global $l10n;

        $domains = $l10n ? array_keys($l10n) : array();

        load_default_textdomain($locale);

        foreach ($domains as $domain) {
            // The default text domain is handled by `load_default_textdomain()`.
            if ('default' === $domain) {
                continue;
            }

            /*
             * Unload current text domain but allow them to be reloaded
             * after switching back or to another locale.
             */
            unload_textdomain($domain, true);
            get_translations_for_domain($domain);
        }
    }
}