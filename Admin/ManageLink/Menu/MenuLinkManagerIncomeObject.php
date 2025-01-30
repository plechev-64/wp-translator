<?php

namespace Src\Module\Translator\Admin\ManageLink\Menu;

use Src\Module\Translator\Entity\TranslatorEntity;
use Src\Module\Translator\Admin\ManageLink\LinkManagerIncomeObject;
use WP_Term;

class MenuLinkManagerIncomeObject extends LinkManagerIncomeObject {
	public WP_Term $term;
	/** @var array<TranslatorEntity> */
	public array $translateEntities;

	/**
	 * @param WP_Term $term
	 * @param TranslatorEntity[] $translateEntities
	 */
	public function __construct( WP_Term $term, array $translateEntities ) {
		$this->term              = $term;
		$this->translateEntities = $translateEntities;
	}


}