<?php

namespace Src\Module\Translator\LanguageCodeChanger;

use Src\Module\Translator\Config;
use Src\Module\Translator\Enum\UrlStrategy;
use Src\Module\Translator\Translator;

class LanguageCodeUrlStrategyChanger implements LanguageCodeChangerInterface {

	public function isSupport(): bool {
		global $wp_query, $wp;

		if(! empty( $GLOBALS['wp']->query_vars['rest_route'] )){
			return false;
		}

		return Config::CURRENT_URL_STRATEGY === UrlStrategy::Directory && ( $wp->matched_rule || $wp_query->get( 'language' ) || ( ! $wp_query->query && $wp_query->is_page ) );
	}

	public function change(): string {
		global $wp_query;

		if ( $wp_query->get( 'language' ) ) {
			return $wp_query->get( 'language' );
		} else if ( ! $wp_query->query ) {
			return Config::getDefaultLanguage();
		}

		return $this->getLanguageCodeByRequestUri($_SERVER['REQUEST_URI']);

	}

    public function getLanguageCodeByRequestUri(string $requestUri): string {
        $languagesExceptDefault = array_filter( Config::getUsingLanguages(), function ( $language ) {
            return $language !== Config::getDefaultLanguage();
        } );

        preg_match( '/(' . implode( '\/|', $languagesExceptDefault ) . '\/)/', $requestUri, $matches );

        return !empty($matches[0]) ? $matches[0] : Config::getDefaultLanguage();
    }

}