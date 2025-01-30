<?php

namespace Src\Module\Translator\Admin;

use Src\Module\Translator\Admin\LanguageColumn\TermsLanguageColumn;
use Src\Module\Translator\Admin\LineSwitcher\TermLanguageSwitcher;
use Src\Module\Translator\Admin\Metabox\TermLanguageMetabox;
use Src\Module\Translator\Config;
use Src\Module\Translator\LocalizeMainConfig;
use WP_Screen;

class TermPagePartManager {

	public function __construct(
		private readonly TermsLanguageColumn $termsLanguageColumn,
		private readonly TermLanguageMetabox $termLanguageMetabox,
		private readonly TermLanguageSwitcher $termLanguageSwitcher,
		private readonly LocalizeMainConfig $localizeMainConfig,
	) {
	}

	public function init( WP_Screen $screen ): void {

		if ( $screen->base === 'edit-tags' ) {
			$this->termsLanguageColumn->init();
			add_action( 'admin_footer', function () {
				wp_register_script( 'translator-default-termdata', Config::getAssetsDirUri() . '/js/admin-default-termdata.js' );
				wp_localize_script( 'translator-default-termdata', 'TRL', $this->localizeMainConfig->get() );
				wp_enqueue_script( 'translator-default-termdata' );
				$this->termLanguageSwitcher->registerFooterScripts();
			} );
		}

		$this->termLanguageMetabox->init();

	}
}