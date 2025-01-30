<?php

namespace Src\Module\Translator\Model;

class PostsFilter
{
    public ?string $postType = null;
    public ?array $statuses = null;
    public ?int $authorId = null;
}