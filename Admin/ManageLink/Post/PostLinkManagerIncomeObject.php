<?php

namespace Src\Module\Translator\Admin\ManageLink\Post;

use Src\Module\Translator\Entity\TranslatorEntity;
use Src\Module\Translator\Admin\ManageLink\LinkManagerIncomeObject;
use WP_Post;

class PostLinkManagerIncomeObject extends LinkManagerIncomeObject{
	public WP_Post $post;
	/** @var array<TranslatorEntity> */
	public array $translateEntities;

	/**
	 * @param WP_Post $post
	 * @param array $translateEntities
	 */
	public function __construct( WP_Post $post, array $translateEntities ) {
		$this->post              = $post;
		$this->translateEntities = $translateEntities;
	}

}