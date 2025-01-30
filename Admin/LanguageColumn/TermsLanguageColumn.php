<?php

namespace Src\Module\Translator\Admin\LanguageColumn;

use Src\Module\Translator\Admin\ManageLink\Term\TermLinkManager;
use Src\Module\Translator\Admin\ManageLink\Term\TermLinkManagerIncomeObject;
use Src\Module\Translator\Enum\EntityType;
use Src\Module\Translator\Model\TranslatorEntityObject;
use Src\Module\Translator\Service\TranslatorEntityService;
use Src\Module\Translator\Service\TranslatorLanguageService;
use Src\Module\Translator\TranslatorState;

class TermsLanguageColumn extends AbstractLanguageColumn {

	public function __construct(
		private readonly TermLinkManager $postLinkManager,
		private readonly TranslatorEntityService $translatorEntityService,
		TranslatorState $state,
		TranslatorLanguageService $translatorService
	) {
		parent::__construct( $state, $translatorService );
	}

	public function init(): void {
		$taxonomy = $_REQUEST['taxonomy'] ?? 'tags';
		add_filter( 'manage_edit-' . $taxonomy . '_columns', function ( array $columns ) {
			return $this->addManagementColumn( $columns );
		} );

		if ( $this->isShowColumnContent( $taxonomy ) ) {
			add_action( 'manage_' . $taxonomy . '_custom_column', function ( string $string, string $column_name, ?int $termId = null ) {
				$this->addContentTermsManagementColumn( $column_name, $termId );
			}, 10, 3 );
		}
	}

	/**
	 * Add posts management column.
	 *
	 * @param string $column_name Name of the column.
	 * @param int|null $termId Term ID.
	 */
	private function addContentTermsManagementColumn( string $column_name, ?int $termId = null ): void {
		global $term;

		if ( ! $termId ) {
			$termId = $term->term_id;
		}

		if ( self::COLUMN_KEY !== $column_name ) {
			return;
		}

		$translateEntities = $this->translatorEntityService->getTranslateEntitiesByObject(
			new TranslatorEntityObject( $termId, EntityType::Tax )
		);

		$links = $this->postLinkManager->getLinks(
			new TermLinkManagerIncomeObject( get_term( $termId ), $_REQUEST['post_type'], $translateEntities )
		);

		echo $this->getManagerLinks( $links );

	}
}