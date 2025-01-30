<?php

namespace Src\Module\Translator\Admin\Metabox;

use Doctrine\ORM\EntityManagerInterface;
use Src\Module\Translator\Entity\TranslatorEntity;
use Src\Module\Translator\Entity\TranslatorLanguage;
use Src\Module\Translator\Admin\ManageLink\Term\TermLinkManager;
use Src\Module\Translator\Admin\ManageLink\Term\TermLinkManagerIncomeObject;
use Src\Module\Translator\Admin\ManageLink\Term\TranslateTermManageLink;
use Src\Module\Translator\Config;
use Src\Module\Translator\Enum\EntityType;
use Src\Module\Translator\Model\TranslatorEntityObject;
use Src\Module\Translator\Service\TranslatorEntityService;
use Src\Module\Translator\Service\TranslatorLanguageService;
use Src\Module\Translator\TranslatorState;
use Src\Core\Transformer\TransformerManager;
use WP_Term;

class TermLanguageMetabox extends AbstractLanguageMetabox {

	public function __construct(
		private readonly TermLinkManager $termLinkManager,
        private  readonly TranslatorEntityService $translatorEntityService,
		private readonly TranslatorLanguageService $translatorLanguageService,
		private readonly TranslatorState $state,
		TransformerManager $transformerManager,
		EntityManagerInterface $entityManager
	) {
		parent::__construct(
			$translatorEntityService,
			$transformerManager,
			$entityManager
		);
	}

	public function getEntityType(): EntityType {
		return EntityType::Tax;
	}

	public function getProps(): PropsMetabox {
		$props             = new PropsMetabox( 'trl-language', __( 'Язык публикации', 'trl' ) );
		$props->taxonomies = Config::getTaxonomies();

		return $props;
	}

	/**
	 * @param WP_Term|string $item
	 *
	 * @return string
	 */
	public function getContent( mixed $item ): string {

		$entityLangCode = $this->state->getCurrentLanguage()->code;
		$postType       = $_GET['post_type'] ?? 'post';
		$sourceId       = (int) $_GET['trl_source'] ?? null;
		$entityId       = $sourceId ?: ( is_string( $item ) ? null : $item->term_id );
		$group          = null;

		$translateEntities = [];
		if ( $entityId ) {
			$translateEntities = $this->translatorEntityService->getTranslateEntitiesByObject(
				new TranslatorEntityObject( $entityId, $this->getEntityType() )
			);
		}

		if ( $translateEntities ) {
			/** @var TranslatorEntity $translateEntity */
			foreach ( $translateEntities as $translateEntity ) {
				if ( $translateEntity->getEntityId() === $entityId ) {
					if ( ! $sourceId ) {
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
            #trl-metabox {
                padding: 10px;
                background: #fff;
            }

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
        </style>
        <div id="trl-metabox">
            <input type="hidden" name="trl_group_id" value="<?php echo $group ? $group->getId() : 0 ?>">
            <div class="field field-current-language">
                <label>
                    Текущий язык
                    <select name="trl_code">
						<?php
						/** @var TranslatorLanguage $language */
						foreach ( $this->translatorLanguageService->getUsingLanguages() as $language ):
							/** @var TranslatorEntity $translateEntity */
							foreach ( $translateEntities as $translateEntity ) {
								if (
									$translateEntity->getCodeLang() === $language->getCode() &&
									$item instanceof WP_Term &&
									$translateEntity->getEntityId() != $item->term_id
								) {
									continue 2;
								}
							}
							?>
                            <option value="<?php echo $language->getCode() ?>" <?php selected( $language->getCode(), $entityLangCode ); ?>><?php echo $language->getEnglishName() ?></option>
						<?php
						endforeach;
						?>
                    </select>
                </label>
            </div>
			<?php if ( $item instanceof WP_Term ): ?>
                <div class="field field-other-languages">
					<?php

					$links = $this->termLinkManager->getLinks(
						new TermLinkManagerIncomeObject( $item, $postType, $translateEntities )
					);

					/** @var TranslatorLanguage $language */
					foreach ( $this->translatorLanguageService->getUsingLanguages() as $language ):
						if ( $language->getCode() === $entityLangCode ) {
							continue;
						}
						?>
                        <div class="row-language">
                            <span><?php echo $language->getEnglishName(); ?></span>
							<?php
							/** @var TranslateTermManageLink $link */
							foreach ( $links as $link ):
								if ( $link->langCode !== $language->getCode() ) {
									continue;
								}
								?>
                                <a href="<?php echo $link->getUrl(); ?>">
                                    <i class="dashicons <?php echo( $link->isCurrent() ? 'dashicons-edit' : 'dashicons-plus-alt2' ); ?>"></i>
                                </a>
							<?php
							endforeach;
							?>
                        </div>
					<?php
					endforeach;
					?>
                </div>
			<?php endif; ?>
        </div>
		<?php
		return ob_get_clean();
	}

}
