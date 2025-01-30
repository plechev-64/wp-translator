<?php

namespace Src\Module\Translator;

use Src\Module\Translator\Enum\UrlStrategy;

class Config
{
    public const ALL_LANGUAGES_CODE = 'all';
    public const IS_TRANSLATE_MENU = true;
    public const CURRENT_URL_STRATEGY = UrlStrategy::Directory;

    public const OPTION_ACTIVE = 'options_trl_active';
    public const OPTION_DEFAULT_LANGUAGE = 'options_trl_default_language';
    public const OPTION_USING_LANGUAGES = 'options_trl_using_languages';
    public const OPTION_TAXONOMIES = 'options_trl_taxonomies';
    public const OPTION_POST_TYPES = 'options_trl_post_types';

    private static bool $isActive;
    private static string $defaultLanguage = 'ru';
    private static array $usingLanguages;
    private static array $taxonomies;
    private static array $postTypes;

    public static function init(): void
    {

        self::$isActive = (bool)get_option(self::OPTION_ACTIVE);
        self::$defaultLanguage = get_option(self::OPTION_DEFAULT_LANGUAGE, self::$defaultLanguage);
        self::$usingLanguages = get_option(self::OPTION_USING_LANGUAGES, []) ?: [];
        self::$taxonomies = get_option(self::OPTION_TAXONOMIES, []) ?: [];
        self::$postTypes = get_option(self::OPTION_POST_TYPES, []) ?: [];

    }

    /**
     * @return bool
     */
    public static function isActive(): bool
    {
        return self::$isActive;
    }

    public static function addUsingLanguage(string $languageCode): void
    {
        self::$usingLanguages[] = $languageCode;
        update_option(self::OPTION_USING_LANGUAGES, self::$usingLanguages);
    }

    /**
     * @return string
     */
    public static function getDefaultLanguage(): string
    {
        return self::$defaultLanguage;
    }

    /**
     * @return array
     */
    public static function getUsingLanguages(): array
    {
        if (isset(self::$usingLanguages)) {
            return self::$usingLanguages;
        }

        return [];
    }

    /**
     * @return array
     */
    public static function getTaxonomies(): array
    {
        $taxonomies = self::$taxonomies;
        if (self::IS_TRANSLATE_MENU) {
            $taxonomies[] = 'nav_menu';
        }
        return $taxonomies;
    }

    /**
     * @return array
     */
    public static function getPostTypes(): array
    {
        return self::$postTypes;
    }

    public static function getAssetsDirUri(): string
    {
        return ASSETS_URL . '/translator';
    }
}