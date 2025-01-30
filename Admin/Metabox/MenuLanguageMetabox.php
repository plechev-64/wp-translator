<?php

namespace Src\Module\Translator\Admin\Metabox;

use Doctrine\ORM\EntityManagerInterface;
use Src\Module\Translator\Entity\TranslatorEntity;
use Src\Module\Translator\Entity\TranslatorLanguage;
use Src\Module\Translator\Admin\ManageLink\Menu\MenuLinkManager;
use Src\Module\Translator\Admin\ManageLink\Menu\MenuLinkManagerIncomeObject;
use Src\Module\Translator\Admin\ManageLink\Term\TranslateTermManageLink;
use Src\Module\Translator\Config;
use Src\Module\Translator\Enum\EntityType;
use Src\Module\Translator\LocalizeMainConfig;
use Src\Module\Translator\Model\TranslatorEntityObject;
use Src\Module\Translator\Service\TranslatorEntityService;
use Src\Module\Translator\Service\TranslatorLanguageService;
use Src\Module\Translator\TranslatorState;
use Src\Core\Transformer\TransformerManager;
use WP_Term;

class MenuLanguageMetabox extends AbstractLanguageMetabox {

	public function __construct(
		private readonly LocalizeMainConfig $localizeMainConfig,
		private readonly MenuLinkManager $termLinkManager,
        private readonly TranslatorEntityService $translatorEntityService,
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
		$props->taxonomies = [ 'nav_menu' ];
		$props->hasNonce   = false;

		return $props;
	}

	public function init(): void {

		parent::init();

		if ( ! isset( $_GET['menu'] ) ) {
			add_filter( 'wp_get_nav_menu_object', function ( $menu_obj ) {
				return $this->getCorrectFirstMenu( $menu_obj );
			}, 10 );
		}

		add_action('pre_get_posts', function($query){
			if(!empty($query->query['suppress_filters'])){
				$query->query['suppress_filters'] = 0;
				$query->query_vars['suppress_filters'] = 0;
			}
		});

		add_action( 'admin_footer', function () {
			$this->registerFooterScripts();
		} );

	}

	public function registerFooterScripts(): void {
		wp_register_script( 'translator-menu-switcher', Config::getAssetsDirUri() . '/js/admin-menu-switcher.js' );
		wp_localize_script( 'translator-menu-switcher', 'TRL', $this->localizeMainConfig->get() );
		wp_enqueue_script( 'translator-menu-switcher' );
	}

	private function getCorrectFirstMenu( WP_Term|false $menu_obj ): WP_Term|false {
		if ( !$menu_obj || ! $menu_obj?->term_id ) {
			return $menu_obj;
		}

		$currentLanguage = $this->state->getCurrentLanguage();

		$menuId = $this->getCorrectMenuIdByLangCodeAndMenuId( $currentLanguage->code, $menu_obj->term_id );

		if ( $menuId ) {
			$menu_obj = get_term( $menuId );
		}

		return ! $menu_obj instanceof \WP_Error ? $menu_obj : false;
	}

	public function getCorrectMenuIdByLangCodeAndMenuId( string $languageCode, int $menuId ): ?int {

		$entities = $this->translatorEntityService->getTranslateEntitiesByObject(
			new TranslatorEntityObject( $menuId, EntityType::Tax )
		);

		if ( $entities ) {
			foreach ( $entities as $entity ) {
				if ( $entity->getCodeLang() === $languageCode ) {
					return $entity->getEntityId();
				}
			}
		}

		$firstEntity = $this->translatorEntityService->getFirstTranslationEntityByCodeAndType( $languageCode, EntityType::Tax );

		return $firstEntity?->getEntityId();

	}

	/**
	 * @param WP_Term|string $item
	 *
	 * @return string
	 */
	public function getContent( mixed $item ): string {

		$entityLangCode = $this->state->getCurrentLanguage()->code;
		$sourceId       = (int) $_GET['trl_source'] ?? null;
		$entityId       = $sourceId ?: ( is_string( $item ) || empty( $item ) ? null : $item->term_id );
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
                max-width: 500px;
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
						new MenuLinkManagerIncomeObject( $item, $translateEntities )
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
