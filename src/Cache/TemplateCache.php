<?php

declare(strict_types=1);

namespace App\Cache;

/**
 * Empties the compiled templates Twig writes in production.
 *
 * Twig stores each template as a generated PHP class and reuses it as long as
 * the file is there. Nothing else in the project removes those classes, so a
 * template sent to a host would keep being served from the previous build
 * until this runs.
 */
final class TemplateCache
{
    public function __construct(private readonly string $directory)
    {
    }

    /** @return int the number of compiled files removed */
    public function clear(): int
    {
        return $this->clearFolder($this->directory);
    }

    /** Twig nests its classes one folder deep, named after a hash. */
    private function clearFolder(string $folder): int
    {
        if (!is_dir($folder)) {
            return 0;
        }

        $removed = 0;

        foreach (glob("{$folder}/*") ?: [] as $entry) {
            if (is_dir($entry)) {
                $removed += $this->clearFolder($entry);
                @rmdir($entry);
                continue;
            }

            if (@unlink($entry)) {
                $removed++;
            }
        }

        return $removed;
    }
}
