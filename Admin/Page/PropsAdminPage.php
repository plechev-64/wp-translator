<?php

namespace Src\Module\Translator\Admin\Page;

class PropsAdminPage {
	public string $id;
	public string $title;
	public int $priority = 10;
	public mixed $counter = null;
	public string $right = 'manage_options';
	public ?string $parent = '';


	/**
	 * @param string $id
	 * @param string $title
	 */
	public function __construct( string $id, string $title ) {
		$this->id    = $id;
		$this->title = $title;
	}

}