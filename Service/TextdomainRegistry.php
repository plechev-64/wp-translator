<?php

namespace Src\Module\Translator\Service;

/**
 * @description переписываем порядок получения путей до файлов локализации из custom_paths,
 * в WP_Textdomain_Registry::333 зачем то стоит проверка на en_US, убираем ее
 */
class TextdomainRegistry extends \WP_Textdomain_Registry
{

    /**
     * Returns the languages directory path for a specific domain and locale.
     *
     * @param string $domain Text domain.
     * @param string $locale Locale.
     *
     * @return string|false MO file path or false if there is none available.
     * @since 6.1.0
     *
     */
    public function get($domain, $locale)
    {
        if (isset($this->all[$domain][$locale])) {
            return $this->all[$domain][$locale];
        }

        return $this->getPathFromLangDir($domain, $locale);
    }

    /**
     * Returns possible language directory paths for a given text domain.
     *
     * @param string $domain Text domain.
     *
     * @return string[] Array of language directory paths.
     * @since 6.2.0
     *
     */
    private function getPathsForDomain($domain)
    {
        $locations = array(
            WP_LANG_DIR . '/plugins',
            WP_LANG_DIR . '/themes',
        );

        if (isset($this->custom_paths[$domain])) {
            $locations[] = $this->custom_paths[$domain];
        }

        return $locations;
    }

    /**
     * Gets the path to the language directory for the current domain and locale.
     *
     * Checks the plugins and themes language directories as well as any
     * custom directory set via {@see load_plugin_textdomain()} or {@see load_theme_textdomain()}.
     *
     * @param string $domain Text domain.
     * @param string $locale Locale.
     *
     * @return string|false Language directory path or false if there is none available.
     * @see _get_path_to_translation_from_lang_dir()
     *
     * @since 6.1.0
     *
     */
    private function getPathFromLangDir($domain, $locale)
    {
        $locations = $this->getPathsForDomain($domain);

        $found_location = false;

        foreach ($locations as $location) {
            $files = $this->get_language_files_from_path($location);

            $mo_path  = "$location/$domain-$locale.mo";
            $php_path = "$location/$domain-$locale.l10n.php";

            foreach ($files as $file_path) {
                if (
                    ! in_array($domain, $this->domains_with_translations, true) &&
                    str_starts_with(str_replace("$location/", '', $file_path), "$domain-")
                ) {
                    $this->domains_with_translations[] = $domain;
                }

                if ($file_path === $mo_path || $file_path === $php_path) {
                    $found_location = rtrim($location, '/') . '/';
                    break 2;
                }
            }
        }

        if ($found_location) {
            $this->set($domain, $locale, $found_location);

            return $found_location;
        }

        /*
         * If no path is found for the given locale and a custom path has been set
         * using load_plugin_textdomain/load_theme_textdomain, use that one.
         */
        if (isset($this->custom_paths[$domain])) {
            $fallback_location = rtrim($this->custom_paths[$domain], '/') . '/';
            $this->set($domain, $locale, $fallback_location);

            return $fallback_location;
        }

        $this->set($domain, $locale, false);

        return false;
    }
}