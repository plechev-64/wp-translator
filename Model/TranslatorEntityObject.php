<?php

namespace Src\Module\Translator\Model;

use Src\Module\Translator\Enum\EntityType;

class TranslatorEntityObject {
	public int $id;
	public EntityType $type;

	/**
	 * @param int $id
	 * @param EntityType $type
	 */
	public function __construct( int $id, EntityType $type ) {
		$this->id   = $id;
		$this->type = $type;
	}

}