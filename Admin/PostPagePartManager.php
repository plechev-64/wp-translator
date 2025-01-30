<?php

namespace Src\Module\Translator\Admin;

use Src\Module\Translator\Admin\LanguageColumn\PostsLanguageColumn;
use Src\Module\Translator\Admin\LineSwitcher\PostsLanguageSwitcher;
use Src\Module\Translator\Admin\Metabox\PostLanguageMetabox;
use Src\Module\Translator\Service\TranslatorPostService;
use WP_Post;
use WP_Screen;

class PostPagePartManager
{

    public function __construct(
        private readonly PostsLanguageSwitcher $postsLanguageSwitcher,
        private readonly PostsLanguageColumn   $postsLanguageColumn,
        private readonly PostLanguageMetabox   $postLanguageMetabox,
        private readonly TranslatorPostService $postService,
    )
    {
    }

    public function init(WP_Screen $screen): void
    {

        if ($screen->base === 'edit') {
            add_action('admin_footer', function () {
                $this->postsLanguageSwitcher->registerFooterScripts();
            });
            $this->postsLanguageColumn->init();
        }

        if ($screen->base === 'post') {
            if ($screen->action === 'add') {
                $sourcePostId = intval($_GET['trl_source'] ?? 0);
                if ($sourcePostId) {
                    $sourcePost = get_post($sourcePostId);
                    add_filter('default_title', function ($title) use ($sourcePost) {
                        return $sourcePost->post_title;
                    }, 10);
                    add_filter('default_content', function ($content) use ($sourcePost) {
                        return $sourcePost->post_content;
                    }, 10);
                    add_action('wp_insert_post', function(int $postId, WP_Post $post, bool $update) use ($sourcePost){
                        if(!$update){
                            $this->postService->savePreEntityForPost($postId, $sourcePost->ID);
                        }
                    }, 10, 3);
                }
            }

            $this->postLanguageMetabox->init();

        }

        /**
         * @description Скрываем блок языков для неопубликованной записи
         * и показываем сразу после публикации
         */
        add_action('admin_footer', function (){
            echo '<script>
            document.addEventListener("DOMContentLoaded", function () {
                if(window.location.pathname.indexOf("post-new.php") < 0){
                    return;
                }
                wp.data.subscribe(() => {
                    const currentPost = wp.data.select("core/editor").getCurrentPost();
                    if (currentPost && currentPost.status === "publish") {
                        document.querySelector(".field-other-languages").classList.remove("hidden");
                    }
                });
            });
            </script>';
        });
    }

    /**
     * @description Очищает блоки в контенте публикации от содержимого
     * @param WP_Post $sourcePost
     *
     * @return string
     */
    private function getDefaultCopyContent(WP_Post $sourcePost): string
    {
        $blocks = parse_blocks($sourcePost->post_content);
        if ($blocks) {
            foreach ($blocks as &$block) {
                if (stripos($block['blockName'], 'acf/') === false) {
                    continue;
                }
                $block['attrs']['data'] = [];
            }
            $content = serialize_blocks($blocks);
        } else {
            $content = $sourcePost->post_content;
        }
        return $content;
    }
}