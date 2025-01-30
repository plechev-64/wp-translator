<?php

namespace Src\Module\Translator\Admin;

use Src\Module\Translator\Admin\Uploader\AbstractFileUploader;
use Src\Module\Translator\Admin\Uploader\FileUploaderProps;

class LanguageFlagUploader extends AbstractFileUploader{

	public function getProps(): FileUploaderProps {
		$props = new FileUploaderProps('language-flag');
		$props->uploadDir = 'flags';
		$props->isGenerateAttachments = false;
		$props->fileTypes = [ 'png', 'jpg', 'svg' ];
		return $props;
	}

}