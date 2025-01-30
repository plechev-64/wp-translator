<?php

namespace Src\Module\Translator\Admin\Metabox;

class PropsMetabox {
	public string $id;
	public bool $hasNonce = true;
	public string $title;
	public string $context = 'normal';
	public string $priority = 'default';
	public array $postTypes = [];
	public array $postIds = [];
	public array $taxonomies = [];

	/**
	 * @param string $id
	 * @param string $title
	 */
	public function __construct( string $id, string $title ) {
		$this->id    = $id;
		$this->title = $title;
	}

}