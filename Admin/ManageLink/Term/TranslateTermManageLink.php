<?php

namespace Src\Module\Translator\Admin\ManageLink\Term;

use Src\Module\Translator\Admin\ManageLink\ManageLinkInterface;

class TranslateTermManageLink implements ManageLinkInterface
{
    public int $termId;
	public string $taxonomy;
    public string $postType;
    public bool $isCurrent = false;
    public string $langCode;

	/**
	 * @param int $termId
	 * @param string $taxonomy
	 * @param string $postType
	 * @param string $langCode
	 */
    public function __construct(int $termId, string $taxonomy, string $postType, string $langCode)
    {
        $this->termId = $termId;
	    $this->taxonomy = $taxonomy;
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
        $this->termId = $entityId;
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
                'taxonomy' => $this->taxonomy,
                'tag_ID' => $this->termId,
                'action' => 'edit',
                'lang' =>  $this->langCode,
            ], admin_url('/term.php'));
        }else{
            return add_query_arg([
                'post_type' => $this->postType,
                'taxonomy' => $this->taxonomy,
                'trl_source' => $this->termId,
                'lang' => $this->langCode,
            ], admin_url('/edit-tags.php'));
        }
    }

}