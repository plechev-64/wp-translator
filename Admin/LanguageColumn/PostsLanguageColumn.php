<?php

namespace Src\Module\Translator\Admin\LanguageColumn;

use Src\Core\Wordpress\PostType\PostTypeManager;
use Src\Module\Translator\Admin\ManageLink\Post\PostLinkManager;
use Src\Module\Translator\Admin\ManageLink\Post\PostLinkManagerIncomeObject;
use Src\Module\Translator\Enum\EntityType;
use Src\Module\Translator\Model\TranslatorEntityObject;
use Src\Module\Translator\Service\TranslatorEntityService;
use Src\Module\Translator\Service\TranslatorLanguageService;
use Src\Module\Translator\TranslatorState;

class PostsLanguageColumn extends AbstractLanguageColumn {

    public function __construct(
        private readonly PostLinkManager $postLinkManager,
        private readonly PostTypeManager $postTypeManager,
        private readonly TranslatorEntityService $translatorEntityService,
        TranslatorState $state,
        TranslatorLanguageService $translatorLanguageService
    ) {
        parent::__construct( $state, $translatorLanguageService );
    }

    public function init(): void {
        $postType = $_REQUEST['post_type'] ?? 'post';

        if(!$this->translatorEntityService->isSupportEntityType(EntityType::Post, $postType)){
            return;
        }

        add_filter( 'manage_' . $postType . '_posts_columns', function ( array $columns ) {
            return $this->addPostsManagementColumn( $columns );
        } );

        if ( $this->isShowColumnContent( $postType ) ) {
            $hierarchicalType = $this->isPostTypeHierarchical( $postType )? 'pages': 'posts';
            add_action( 'manage_'.$hierarchicalType.'_custom_column', function ( string $column_name, ?int $postId = null ) {
                $this->addContentPostsManagementColumn( $column_name, $postId );
            } );
        }
    }

    /**
     * отказался от использования ВП-функции is_post_type_hierarchical, тк для древовидных публикаций
     * объект типа записи еще не определен и функция отдает false, причина непонятна
     *
     * @param string $postType
     *
     * @return bool
     */
    private function isPostTypeHierarchical(string $postType): bool {

        if($postType === 'page'){
            return true;
        }

        $object = $this->postTypeManager->getPostTypeObject( $postType );
        if(!$object){
            return false;
        }

        return $object->args()['hierarchical']?? false;

    }

    /**
     * @param array $columns
     *
     * @return array
     */
    private function addPostsManagementColumn( array $columns ): array {

        if ( 'trash' === get_query_var( 'post_status' ) ) {
            return $columns;
        }

        return $this->addManagementColumn( $columns );

    }

    /**
     * Add posts management column.
     *
     * @param string $column_name
     * @param int|null $postId
     */
    private function addContentPostsManagementColumn( string $column_name, ?int $postId = null ): void {
        global $post;

        if ( ! $postId ) {
            $postId = $post->ID;
        }

        if ( self::COLUMN_KEY !== $column_name ) {
            return;
        }

        $translateEntities = $this->translatorEntityService->getTranslateEntitiesByPostObject(
            new TranslatorEntityObject( $postId, EntityType::Post ), get_post($postId)->post_type
        );

        $links = $this->postLinkManager->getLinks(
            new PostLinkManagerIncomeObject( $post, $translateEntities )
        );

        echo $this->getManagerLinks( $links );

    }
}