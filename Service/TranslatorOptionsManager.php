<?php

namespace Src\Module\Translator\Service;

use Src\Module\Translator\Config;
use Src\Module\Translator\TranslatorState;

class TranslatorOptionsManager
{

    private static array $options = [];

    public function __construct(
        private readonly TranslatorState $state
    ) {
    }

    public function initHooks(): void
    {

        if ( ! self::$options) {
            return;
        }

        foreach (self::$options as $optionName) {
            add_filter("pre_option_{$optionName}", [$this, 'getPreOption'], 10, 2);
            add_action("update_option_{$optionName}", [$this, 'updateOption'], 10, 3);
        }

    }

    public function updateOption(mixed $oldValue, mixed $value, string $optionName): void
    {
        if ($this->state->getCurrentLanguageCode() === Config::getDefaultLanguage()) {
            return;
        }

        global $wpdb;
        $wpdb->update($wpdb->options, [
            'option_value' => $oldValue
        ], [
            'option_name' => $optionName
        ]);

        update_option($optionName . '_' . $this->state->getCurrentLanguageCode(), $value);
    }

    public function getPreOption(mixed $preOption, string $optionName): mixed
    {

        if ($this->state->getCurrentLanguageCode() === Config::getDefaultLanguage()) {
            return $preOption;
        }

        return get_option($optionName . '_' . $this->state->getCurrentLanguageCode()) ?: $preOption;
    }

    public static function addOption(string $optionName): void
    {
        self::$options[] = $optionName;
    }

    public static function addOptions(array $options): void
    {
        foreach ($options as $optionName) {
            self::addOption($optionName);
        }
    }

}