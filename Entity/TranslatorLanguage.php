<?php

namespace Src\Module\Translator\Entity;

use Doctrine\ORM\Mapping as ORM;
use Src\Module\Translator\Config;
use Src\Module\Translator\Model\LanguageFlag;
use Src\Module\Translator\Repository\TranslatorLangRefRepository;

#[ORM\Entity( repositoryClass: TranslatorLangRefRepository::class )]
#[ORM\Table( name: 'wp_translator_languages' )]
#[ORM\UniqueConstraint( name: 'code_idx', columns: [ 'code' ] )]
#[ORM\UniqueConstraint( name: 'english_name_idx', columns: [ 'english_name' ] )]
class TranslatorLanguage {

	#[ORM\Id]
	#[ORM\GeneratedValue( strategy: 'IDENTITY' )]
	#[ORM\Column( name: "id", type: "integer" )]
	private ?int $id = null;

	#[ORM\Column( name: "english_name", type: "string" )]
	private ?string $englishName = null;

	#[ORM\Column( name: "native_name", type: "string", nullable: true )]
	private ?string $nativeName = null;

	#[ORM\Column( name: "code", type: "string" )]
	private ?string $code = null;

	#[ORM\Column( name: "locale", type: "string" )]
	private ?string $locale = null;

	#[ORM\Column( name: "is_custom_image", type: "boolean", nullable: true )]
	private ?bool $isCustomImage = null;

	#[ORM\Column( name: "custom_image_id", type: "integer", nullable: true )]
	private ?int $customImageId = null;

	#[ORM\Column( name: "is_rtl", type: "boolean", nullable: true )]
	private ?bool $isRtl = null;

	/**
	 * @return int|null
	 */
	public function getId(): ?int {
		return $this->id;
	}

	/**
	 * @param int|null $id
	 */
	public function setId( ?int $id ): void {
		$this->id = $id;
	}

	/**
	 * @return string|null
	 */
	public function getEnglishName(): ?string {
		return $this->englishName;
	}

	/**
	 * @param string|null $englishName
	 */
	public function setEnglishName( ?string $englishName ): void {
		$this->englishName = $englishName;
	}

	/**
	 * @return string|null
	 */
	public function getNativeName(): ?string {
		return $this->nativeName ?: $this->englishName;
	}

	/**
	 * @param string|null $nativeName
	 */
	public function setNativeName( ?string $nativeName ): void {
		$this->nativeName = $nativeName;
	}

	/**
	 * @return string|null
	 */
	public function getCode(): ?string {
		return $this->code;
	}

	/**
	 * @param string|null $code
	 */
	public function setCode( ?string $code ): void {
		$this->code = $code;
	}

	/**
	 * @return string|null
	 */
	public function getLocale(): ?string {
		return $this->locale;
	}

	/**
	 * @param string|null $locale
	 */
	public function setLocale( ?string $locale ): void {
		$this->locale = $locale;
	}

	/**
	 * @return bool
	 */
	public function isCustomImage(): bool {
		return $this->isCustomImage ?? false;
	}

	/**
	 * @param bool|null $isCustomImage
	 */
	public function setIsCustomImage( ?bool $isCustomImage ): void {
		$this->isCustomImage = $isCustomImage;
	}

	/**
	 * @return int|null
	 */
	public function getCustomImageId(): ?int {

		if ( ! $this->isCustomImage ) {
			return null;
		}

		return $this->customImageId;
	}

	/**
	 * @param int|null $customImageId
	 */
	public function setCustomImageId( ?int $customImageId ): void {
		$this->customImageId = $customImageId;
	}

	/**
	 * @return bool
	 */
	public function isRtl(): bool {
		return $this->isRtl?? false;
	}

	/**
	 * @param bool $isRtl
	 */
	public function setIsRtl( bool $isRtl ): void {
		$this->isRtl = $isRtl;
	}

	public function getFlag(): LanguageFlag {
		$flag = new LanguageFlag();
		if ( $this->isCustomImage() ) {
			$flag->url = wp_get_attachment_url( $this->getCustomImageId() );
		} else {
			$flag->url = sprintf( '%s/images/flags/%s.png', Config::getAssetsDirUri(), $this->getCode() );
		}

		$flag->image = '<img class="trl-flag"  
					width="20"
					height="12" 
					src="' . $flag->url . '" 
					alt="' . $this->getCode() . '"
				/>';

		return $flag;
	}

}
