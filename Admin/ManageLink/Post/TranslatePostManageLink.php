<?php

namespace Src\Module\Translator\Admin\ManageLink\Post;

use Src\Module\Translator\Admin\ManageLink\ManageLinkInterface;

class TranslatePostManageLink implements ManageLinkInterface
{
    public int $postId;
    public string $postType;
    public bool $isCurrent = false;
    public string $langCode;

    /**
     * @param int $postId
     * @param string $postType
     * @param string $langCode
     */
    public function __construct(int $postId, string $postType, string $langCode)
    {
        $this->postId = $postId;
        $this->postType = $postType;
        $this->langCode = $langCode;
    }

    /**
     * @return bool
     */
    public function isCurrent(): bool
    {
        return $this->isCurrent;
    }


    public function setCurrent(int $entityId): void
    {
        $this->postId = $entityId;
        $this->isCurrent = true;
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        if($this->isCurrent){
            return add_query_arg([
                'post_type' => $this->postType,
                'post' => $this->postId,
                'action' => 'edit',
                'lang' =>  $this->langCode,
            ], admin_url('/post.php'));
        }else{
            return add_query_arg([
                'post_type' => $this->postType,
                'trl_source' => $this->postId,
                'lang' => $this->langCode,
            ], admin_url('/post-new.php'));
        }
    }

}