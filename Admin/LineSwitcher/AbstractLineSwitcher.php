<?php

namespace Src\Module\Translator\Admin\LineSwitcher;

use Src\Module\Translator\Config;
use Src\Module\Translator\DTO\Model\TranslatorLanguageDTO;
use Src\Module\Translator\Service\TranslatorEntityService;
use Src\Module\Translator\Service\TranslatorLanguageService;
use Src\Module\Translator\TranslatorState;

abstract class AbstractLineSwitcher
{
    protected string $postType;
    protected array $postStatus = [];

    abstract function getCounters(): array;

    public function __construct(
        private readonly TranslatorState $state,
        protected readonly TranslatorEntityService $translatorEntityService,
        private readonly TranslatorLanguageService $translatorLanguageService
    )
    {
        $post_statuses   = array_keys ( get_post_stati () );
        $post_status     = !empty($_GET['post_status'])? esc_attr($_GET['post_status']): [];
        if ( is_string ( $post_status ) ) {
            $post_status = $post_status ? [$post_status] : [];
        }

        $illegal_status = array_diff ( $post_status, $post_statuses );
        $this->postStatus = array_diff ( $post_status, $illegal_status );
        $this->postType = !empty($_GET['post_type'])? esc_attr($_GET['post_type']): 'post';
    }

    public function registerFooterScripts(): void
    {
        wp_register_script('translator-line-switcher', Config::getAssetsDirUri() . '/js/admin-line-switcher.js');
        wp_localize_script( 'translator-line-switcher', 'TRLTermLineSwitcher', [
            'languageLinks' => $this->getLocalizeData()
        ] );
        wp_enqueue_script( 'translator-line-switcher' );
    }

    private function getLocalizeData(): array {

        $counters   = $this->getCounters();

        $links = [];
        /** @var TranslatorLanguageDTO $lang */
        foreach ( $this->translatorLanguageService->getUsingWithAllLanguagesModels() as $lang ) {
            $escCode = preg_replace('/\W/', '', $lang->code);
            $links[ ] = [
                'type' => esc_js( $this->postType ),
                'statuses' => array_map( 'esc_js', $this->postStatus ),
                'code' => esc_js( $lang->code ),
                'name' => esc_js( $lang->englishName ),
                'current' => $lang->code === $this->state->getCurrentLanguage()->code,
                'count' => $counters[$escCode]?? 0,
            ];
        }

        return $links;

    }


}