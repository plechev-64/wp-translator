<?php

namespace Src\Module\Translator\Service;

use Src\Module\Translator\Config;
use Src\Module\Translator\TranslatorState;

class TranslatorCookieService {

	public function __construct(
		private readonly TranslatorState $state
	) {
	}

	public function getCookieLanguageCode(): ?string {
		$langCode = $this->get( $this->getCookieName() ) ?: null;

		if ( ! $langCode ) {
			$langCode = Config::getDefaultLanguage();
			$this->setCookieLanguageCode( $langCode );
		}

		return $langCode;
	}

	public function setCookieLanguageCode( string $langCode ): void {
		$this->set( $this->getCookieName(), $langCode );
	}

	public function getCookieName(): string {
		return $this->state->isAdmin() ? $this->getBackendCookieName() : $this->getFrontendCookieName();
	}

	public function getBackendCookieName(): string
    {
		return 'trl_current_admin_language';
	}

	public function getFrontendCookieName(): string
    {
		return 'trl_current_language';
	}

	public function get( $cookieName ): ?string
    {
		$cookie_value = esc_attr( $_COOKIE[ $cookieName ]?? '' );
        return $cookie_value ? substr( $cookie_value, 0, 10 ) : null;
	}


	private function set( $cookieName, $lang_code ): void
    {
		$_COOKIE[ $cookieName ] = $lang_code;
        setcookie(
            $cookieName,
            (string) $lang_code,
            time() + DAY_IN_SECONDS,
            defined( 'COOKIEPATH' ) ? COOKIEPATH : '/'
        );
	}

	/**
	 * @return bool|string
	 */
	private function getCookieDomain(): bool|string
    {

		return defined( 'COOKIE_DOMAIN' ) ? ".".COOKIE_DOMAIN : ".".self::getServerHostName();
	}

	/**
	 * Returns SERVER_NAME, or HTTP_HOST if the first is not available
	 *
	 * @return string
	 */
	private static function getServerHostName(): string
    {
		$host = '';
		if ( isset( $_SERVER['HTTP_HOST'] ) ) {
			$host = $_SERVER['HTTP_HOST'];
		} elseif ( isset( $_SERVER['SERVER_NAME'] ) ) {
			$host = $_SERVER['SERVER_NAME'] . self::getPort();
			// Removes standard ports 443 (80 should be already omitted in all cases)
			$host = preg_replace( '@:[443]+([/]?)@', '$1', $host );
		}

		return $host;
	}

	private static function getPort(): string
    {
		return isset( $_SERVER['SERVER_PORT'] ) && ! in_array( $_SERVER['SERVER_PORT'], [ 80, 443 ] )
			? ':' . $_SERVER['SERVER_PORT']
			: '';
	}

}
