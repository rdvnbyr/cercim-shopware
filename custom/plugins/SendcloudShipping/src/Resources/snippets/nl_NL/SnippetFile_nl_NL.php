<?php

namespace Sendcloud\Shipping\Resources\snippets\nl_NL;

use Shopware\Core\System\Snippet\Files\SnippetFileInterface;

/**
 * Class SnippetFile_nl_NL
 *
 * @package Sendcloud\Shipping\Resources\snippets\nl_NL
 */
class SnippetFile_nl_NL implements SnippetFileInterface
{
    public function getName(): string
    {
        return 'sendcloud.nl-NL';
    }

    public function getPath(): string
    {
        return __DIR__ . '/sendcloud.nl-NL.json';
    }

    public function getIso(): string
    {
        return 'nl-NL';
    }

    public function getAuthor(): string
    {
        return 'Sendcloud';
    }

    public function isBase(): bool
    {
        return false;
    }
}
