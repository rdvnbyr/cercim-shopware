<?php

namespace Sendcloud\Shipping\Resources\snippets\fr_FR;

use Shopware\Core\System\Snippet\Files\SnippetFileInterface;

/**
 * Class SnippetFile_fr_FR
 *
 * @package Sendcloud\Shipping\Resources\snippets\fr_FR
 */
class SnippetFile_fr_FR implements SnippetFileInterface
{
    public function getName(): string
    {
        return 'sendcloud.fr-FR';
    }

    public function getPath(): string
    {
        return __DIR__ . '/sendcloud.fr-FR.json';
    }

    public function getIso(): string
    {
        return 'fr-FR';
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