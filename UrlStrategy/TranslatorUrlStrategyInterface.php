<?php

namespace Src\Module\Translator\UrlStrategy;

interface TranslatorUrlStrategyInterface
{
    public function init(): void;
    public function transformUrl(string $url): string;
}