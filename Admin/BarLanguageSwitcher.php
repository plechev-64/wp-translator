<?php

namespace Src\Module\Translator\Admin;

use Src\Module\Translator\DTO\Model\TranslatorLanguageDTO;
use Src\Module\Translator\Service\TranslatorLanguageService;
use Src\Module\Translator\TranslatorState;
use Src\Core\Transformer\Exception\TransformerException;
use WP_Admin_Bar;

class BarLanguageSwitcher {
	private const FLAG_KSES_TAGS = array(
		'img' => array(
			'src'    => array(),
			'class'  => array(),
			'height' => array(),
			'width'  => array(),
		),
		'i'   => array(
			'class' => array(),
		),
	);

	public function __construct(
		private readonly TranslatorState $state,
		private readonly TranslatorLanguageService $translatorLanguageService
	) {
	}

	/**
	 * @throws TransformerException
	 */
	public function renderAdminBarMenu(): void {

		$currentLanguage = $this->state->getCurrentLanguage();
		$usingLanguages  = $this->translatorLanguageService->getUsingWithAllLanguagesModels();

		/** @var WP_Admin_Bar $wp_admin_bar */
		global $wp_admin_bar;

		$parent = 'translator';

		// Current language
		$wp_admin_bar->add_menu(
			array(
				'parent' => false,
				'id'     => $parent,
				'title'  => '<span title="' . __( 'Контент отображается на:', 'trl' ) . ' ' . $currentLanguage->englishName . '">'
				            . wp_kses( $currentLanguage->flag->image, self::FLAG_KSES_TAGS ) . '&nbsp;' . esc_html( $currentLanguage->englishName )
				            . '</span>',
				'href'   => false,
			)
		);

		if ( $usingLanguages ) {
			/** @var TranslatorLanguageDTO $usingLanguage */
			foreach ( $usingLanguages as $usingLanguage ) {
				if ( $usingLanguage->code == $currentLanguage->code ) {
					continue;
				}
				$wp_admin_bar->add_menu(
					array(
						'parent' => $parent,
						'id'     => $parent . '_' . $usingLanguage->code,
						'title'  => wp_kses( $usingLanguage->flag->image, self::FLAG_KSES_TAGS ) . '&nbsp;' . esc_html( $usingLanguage->englishName ),
						'href'   => add_query_arg( [
							'lang'      => $usingLanguage->code,
							'admin-bar' => 1
						] ),
						'meta'   => array(
							'title' => __( 'Показывать контент на:', 'trl' ) . ' ' . $usingLanguage->englishName,
						),
					)
				);
			}
		}
	}
}