<?php

/*
 * This file is part of the Sylius package.
 *
 * (c) Sylius Sp. z o.o.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Sylius\PdfBundle\Filesystem\Symfony;

use Sylius\PdfBundle\Core\Filesystem\Storage\PdfStorageInterface;
use Sylius\PdfBundle\Core\Model\PdfFile;
use Symfony\Component\Filesystem\Filesystem;

final class SymfonyFilesystemPdfStorage implements PdfStorageInterface
{
    public function __construct(
        private readonly Filesystem $filesystem,
        private readonly string $directory,
    ) {
    }

    public function save(PdfFile $file): void
    {
        $path = $this->resolvePath($file->filename());
        $this->filesystem->dumpFile($path, $file->content());

        $file->setStoragePath($path);
    }

    public function remove(string $filename): void
    {
        $this->filesystem->remove($this->resolvePath($filename));
    }

    public function has(string $filename): bool
    {
        return is_file($this->resolvePath($filename));
    }

    public function get(string $filename): PdfFile
    {
        $path = $this->resolvePath($filename);

        if (!is_file($path)) {
            throw new \RuntimeException(sprintf('PDF file "%s" not found.', $filename));
        }

        $content = file_get_contents($path);
        if (false === $content) {
            throw new \RuntimeException(sprintf('Could not read PDF file "%s".', $filename));
        }

        $file = new PdfFile($filename, $content);
        $file->setStoragePath($path);

        return $file;
    }

    public function resolveLocalPath(string $filename): string
    {
        return $this->resolvePath($filename);
    }

    private function resolvePath(string $filename): string
    {
        if ($filename !== basename($filename)) {
            throw new \InvalidArgumentException(sprintf('Invalid PDF filename "%s": directory separators are not allowed.', $filename));
        }

        return rtrim($this->directory, \DIRECTORY_SEPARATOR) . \DIRECTORY_SEPARATOR . $filename;
    }
}
