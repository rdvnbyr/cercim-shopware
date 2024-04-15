<?php

namespace Sendcloud\Shipping\Resources\snippets\es_ES;

use Shopware\Core\System\Snippet\Files\SnippetFileInterface;

/**
 * Class SnippetFile_es_ES
 *
 * @package Sendcloud\Shipping\Resources\snippets\es_ES
 */
class SnippetFile_es_ES implements SnippetFileInterface
{
    public function getName(): string
    {
        return 'sendcloud.es-ES';
    }

    public function getPath(): string
    {
        return __DIR__ . '/sendcloud.es-ES.json';
    }

    public function getIso(): string
    {
        return 'es-ES';
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