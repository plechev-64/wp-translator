<?php

namespace Src\Module\Translator\Admin\ManageLink\Menu;

use Src\Module\Translator\Admin\ManageLink\Term\TranslateTermManageLink;

class TranslateMenuManageLink extends TranslateTermManageLink
{

	/**
	 * @param int $termId
	 * @param string $langCode
	 */
	public function __construct(int $termId, string $langCode)
	{
		parent::__construct($termId, 'nav_menu', 'post', $langCode);
	}

    /**
     * @return string
     */
    public function getUrl(): string
    {
        if($this->isCurrent){
            return add_query_arg([
                'menu' => $this->termId,
                'lang' =>  $this->langCode,
            ], admin_url('/nav-menus.php'));
        }else{
            return add_query_arg([
                'action' => 'edit',
                'menu' => 0,
                'trl_source' => $this->termId,
                'lang' => $this->langCode,
            ], admin_url('/nav-menus.php'));
        }
    }

}