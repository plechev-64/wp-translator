<?php

namespace Src\Module\Translator\Admin\ManageLink;

interface ManageLinkInterface {
	public function isCurrent(): bool;
	public function setCurrent(int $entityId): void;
	public function getUrl(): string;
}