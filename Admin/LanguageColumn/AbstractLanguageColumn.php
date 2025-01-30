<?php

namespace Src\Module\Translator\Admin\LanguageColumn;

use Src\Module\Translator\Entity\TranslatorLanguage;
use Src\Module\Translator\Admin\ManageLink\ManageLinkInterface;
use Src\Module\Translator\Service\TranslatorLanguageService;
use Src\Module\Translator\TranslatorState;

abstract class AbstractLanguageColumn {

	protected const COLUMN_KEY = 'trl_column';

	public function __construct(
		private readonly TranslatorState $state,
		private readonly TranslatorLanguageService $translatorLanguageService
	) {
	}

	/**
	 * @param array $columns
	 *
	 * @return array
	 */
	protected function addManagementColumn( array $columns ): array {
		$newColumns = $columns;

		$flagsColumn = $this->getFlagsColumn();

		if ( $flagsColumn ) {
			$newColumns = [];
			foreach ( $columns as $columnKey => $columnContent ) {
				$newColumns[ $columnKey ] = $columnContent;
				if ( ( 'title' === $columnKey || 'name' === $columnKey ) && ! isset( $newColumns[ self::COLUMN_KEY ] ) ) {
					$newColumns[ self::COLUMN_KEY ] = $flagsColumn;
				}
			}
		}

		return $newColumns;
	}

	protected function getFlagsColumn(): string {
		$usingLanguages = $this->translatorLanguageService->getUsingLanguages();
		if ( count( $usingLanguages ) <= 1 ) {
			return '';
		}

		$currentLanguage = $this->state->getCurrentLanguage();

		$flags_column = '<span class="screen-reader-text">' . esc_html__( 'Все языки', 'trl' ) . '</span>';

		$flags = [];
		/** @var TranslatorLanguage $language */
		foreach ( $usingLanguages as $language ) {
			if ( $language->getCode() === $currentLanguage->code ) {
				continue;
			}
			$flags[] = $language->getFlag()->image;
		}

		$flags_column .= implode( ' ', $flags );

		return $flags_column;
	}

	protected function isShowColumnContent( string $itemType ): bool {

        if ( ! function_exists( 'wp_get_current_user' ) ) {
            wp_cookie_constants();
            require ABSPATH . WPINC . '/pluggable.php';
        }

		$user           = get_current_user_id();
		$hidden_columns = get_user_meta( $user, 'manageedit-' . $itemType . 'columnshidden', true );
		if ( '' === $hidden_columns ) {
			return true;
		}

		return ! is_array( $hidden_columns ) || ! in_array( self::COLUMN_KEY, $hidden_columns, true );
	}

	/**
	 * @param ManageLinkInterface[] $links
	 *
	 * @return string
	 */
	protected function getManagerLinks( array $links ): string {

		$currentLanguage = $this->state->getCurrentLanguage();

		$content = '';
		foreach ( $links as $link ) {
			if ( $link->langCode === $currentLanguage->code ) {
				continue;
			}
			$content .= '<a href="' . $link->getUrl() . '">
                    <i class="dashicons ' . ( $link->isCurrent() ? 'dashicons-edit' : 'dashicons-plus-alt2' ) . '"></i>
                </a>';
		}

		return $content;

	}
}