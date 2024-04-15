<?php declare(strict_types=1);

namespace Sendcloud\Shipping\Resources\snippets\de_DE;

use Shopware\Core\System\Snippet\Files\SnippetFileInterface;

/**
 * Class SnippetFile_de_DE
 *
 * @package Sendcloud\Shipping\Resources\snippets\de_DE
 */
class SnippetFile_de_DE implements SnippetFileInterface
{
    public function getName(): string
    {
        return 'sendcloud.de-DE';
    }

    public function getPath(): string
    {
        return __DIR__ . '/sendcloud.de-DE.json';
    }

    public function getIso(): string
    {
        return 'de-DE';
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
