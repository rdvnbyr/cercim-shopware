<?php declare(strict_types=1);

namespace Sendcloud\Shipping\Resources\snippets\en_GB;

use Shopware\Core\System\Snippet\Files\SnippetFileInterface;

class SnippetFile_en_GB implements SnippetFileInterface
{
    public function getName(): string
    {
        return 'sendcloud.en-GB';
    }

    public function getPath(): string
    {
        return __DIR__ . '/sendcloud.en-GB.json';
    }

    public function getIso(): string
    {
        return 'en-GB';
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
