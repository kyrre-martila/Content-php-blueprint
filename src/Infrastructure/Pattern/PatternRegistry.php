<?php

declare(strict_types=1);

namespace App\Infrastructure\Pattern;

use App\Domain\Pattern\PatternMetadataValidator;
use InvalidArgumentException;

final class PatternRegistry
{
    /** @var array<string, PatternMetadata> */
    private array $patterns = [];

    /** @var array<string, string> */
    private array $viewPaths = [];

    public function __construct(private readonly string $patternsBasePath)
    {
        $this->load();
    }

    /**
     * @return array<string, PatternMetadata>
     */
    public function all(): array
    {
        return $this->patterns;
    }

    public function get(string $key): ?PatternMetadata
    {
        return $this->patterns[$key] ?? null;
    }

    public function exists(string $key): bool
    {
        return isset($this->patterns[$key]);
    }

    public function viewPathFor(string $key): ?string
    {
        return $this->viewPaths[$key] ?? null;
    }

    private function load(): void
    {
        if (!is_dir($this->patternsBasePath)) {
            return;
        }

        $directories = glob($this->patternsBasePath . '/*', GLOB_ONLYDIR);

        if ($directories === false) {
            return;
        }

        sort($directories, SORT_STRING);

        foreach ($directories as $directory) {
            $metadata = $this->loadMetadata($directory);

            if ($metadata === null) {
                continue;
            }

            $this->patterns[$metadata->key()] = $metadata;

            $viewPath = $directory . '/pattern.php';

            if (is_file($viewPath)) {
                $this->viewPaths[$metadata->key()] = $viewPath;
            }
        }

        ksort($this->patterns, SORT_STRING);
        ksort($this->viewPaths, SORT_STRING);
    }

    private function loadMetadata(string $directory): ?PatternMetadata
    {
        $metadataPath = $directory . '/pattern.json';

        if (!is_file($metadataPath)) {
            return null;
        }

        $rawJson = file_get_contents($metadataPath);

        if ($rawJson === false) {
            throw new InvalidArgumentException(sprintf('Pattern metadata invalid in %s: unable to read file', $metadataPath));
        }

        $decoded = json_decode($rawJson, true);

        if (!is_array($decoded)) {
            throw new InvalidArgumentException(sprintf('Pattern metadata invalid in %s: invalid JSON object', $metadataPath));
        }

        (new PatternMetadataValidator($metadataPath))->validate($decoded);

        return PatternMetadata::fromArray($decoded);
    }
}
