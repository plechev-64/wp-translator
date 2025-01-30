<?php

namespace Src\Module\Translator\Admin\ManageLink\Term;

use Src\Module\Translator\Entity\TranslatorEntity;
use Src\Module\Translator\Admin\ManageLink\LinkManagerIncomeObject;
use WP_Term;

class TermLinkManagerIncomeObject extends LinkManagerIncomeObject {
	public WP_Term $term;
	public string $postType;
	/** @var array<TranslatorEntity> */
	public array $translateEntities;

	/**
	 * @param WP_Term $term
	 * @param string $postType
	 * @param TranslatorEntity[] $translateEntities
	 */
	public function __construct( WP_Term $term, string $postType, array $translateEntities ) {
		$this->term              = $term;
		$this->postType          = $postType;
		$this->translateEntities = $translateEntities;
	}


}