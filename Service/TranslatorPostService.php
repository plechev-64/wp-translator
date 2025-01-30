<?php

namespace Src\Module\Translator\Service;

use App\PostType\Document;
use Doctrine\ORM\EntityManagerInterface;
use Src\Core\Entity\Post;
use Src\Core\Transformer\TransformerManager;
use Src\Module\Translator\DTO\Model\TranslatorEntityDTO;
use Src\Module\Translator\Entity\TranslatorEntity;
use Src\Module\Translator\Enum\EntityType;
use Src\Module\Translator\Model\TranslatorEntityObject;

class TranslatorPostService
{

    public function __construct(
        private readonly TranslatorEntityService $entityService,
        private readonly EntityManagerInterface $entityManager,
        private readonly TransformerManager $transformerManager
    ) {
    }

    public function initHooks(): void
    {

        $this->initSlugControl();
        $this->turnOffDefaultPostTemplateWhenTranslationCreate();

    }

    /**
     * @description отключаю дефолтный шаблон для публикации, если создается перевод
     */
    private function turnOffDefaultPostTemplateWhenTranslationCreate(): void
    {

        if (is_admin()) {
            $sourcePostId = (int)($_GET['trl_source'] ?? 0);
            if ($sourcePostId) {
                $sourcePost = get_post($sourcePostId);
                add_filter('register_' . $sourcePost->post_type . '_post_type_args', function ($args) {
                    $args['template'] = [];

                    return $args;
                }, 10);
            }
        }

    }

    private function initSlugControl(): void
    {
        /**
         * 1. Удаляем возможность указания произвольного слага пока перевод
         * не будет опубликован
         */
        add_filter('get_sample_permalink',
            function (array $permalink, int $post_id) {

                $post = get_post($post_id);

                if ( ! $this->entityService->isSupportEntityType(EntityType::Post, $post->post_type)) {
                    return $permalink;
                }

                $sourceEntity = $this->entityService->getSourceEntityByObject(
                    new TranslatorEntityObject($post->ID, EntityType::Post)
                );

                if ( ! $sourceEntity) {
                    return $permalink;
                }

                if ($post->post_status === 'auto-draft' || $post->post_status === 'draft') {
                    $permalink[0] = '';
                }

                return $permalink;
            }, 10, 2);

        /**
         * 2. При публикации перевода или сохранении черновика подставляем слаг родительского материала.
         * После публикации слаг можно менять.
         */
        add_filter('wp_unique_post_slug', function($slug, $postId, $postStatus, $postType, $postParent, $originalSlug){

            if (
                ! $this->entityService->isSupportEntityType(EntityType::Post, $postType)
            ) {
                return $slug;
            }

            /**
             * если публикация уже была раз опубликована, то принимаем любой указанный слаг
             */
            if (
                get_post_meta($postId, 'translate_publish', 1)
            ) {
                return $originalSlug;
            }

            $post = get_post($postId);

            $parentSlug = null;
            if ($post instanceof \WP_Post) {
                $parentSlug = $this->getParentSlugByPost($post);
            }

            return $parentSlug?: $slug;
        }, 10, 6);

        /**
         * 3. Для опубликованного перевода ставим метку, чтобы не приводить
         * слаг к изначальному при следующей публикации из черновика
         */
        add_action('transition_post_status', function (string $newStatus, string $oldStatus, \WP_Post $post): void {
            if ( ! $this->entityService->isSupportEntityType(EntityType::Post,
                    $post->post_type) || $newStatus !== 'publish') {
                return;
            }

            if ( ! get_post_meta($post->ID, 'translate_publish', 1)) {
                update_post_meta($post->ID, 'translate_publish', 1);
            }
        }, 10, 3);

    }


    private function isRequestToPublishPost(): bool
    {
        if (defined('REST_REQUEST') && REST_REQUEST) {
            $request = new \WP_REST_Request();
            $request->set_headers($_SERVER);
            $request->set_body(\WP_REST_Server::get_raw_data());
            $nextStatus = $request->get_json_params()['status'] ?? '';
            $toPublish  = $nextStatus === 'publish' || $nextStatus === 'draft';
        } else {
            $toPublish = isset($_POST['original_post_status']) && $_POST['original_post_status'] === 'auto-draft';
        }

        return $toPublish;
    }

    public function getParentSlugByPost(\WP_Post $post): ?string
    {

        $sourceEntity = $this->entityService->getSourceEntityByObject(
            new TranslatorEntityObject($post->ID, EntityType::Post)
        );

        if ( ! $sourceEntity) {
            return null;
        }

        /** @var Post $post */
        $post = $this->entityManager->getRepository(Post::class)->find($sourceEntity->getEntityId());

        return $post?->getPostName();

    }

    public function savePreEntityForPost(int $itemId, int $sourceId): void
    {

        $sourceEntity = $this->entityService->getEntityByObject(
            new TranslatorEntityObject($sourceId, EntityType::Post)
        );

        $model             = new TranslatorEntityDTO();
        $model->entityId   = $itemId;
        $model->entityType = EntityType::Post;
        $model->codeLang   = '';
        $model->groupId    = $sourceEntity->getGroupId();

        $this->transformerManager->transform($model, TranslatorEntity::class);

        $this->entityManager->flush();

    }

}