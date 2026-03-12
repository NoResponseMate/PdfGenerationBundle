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

namespace Sylius\PdfBundle\Core\Manager;

use Sylius\PdfBundle\Core\Model\PdfFile;
use Symfony\Component\Filesystem\Filesystem;

final class FilesystemPdfFileManager implements PdfFileManagerInterface
{
    /** @param array<string, string> $contextDirectories */
    public function __construct(
        private readonly array $contextDirectories,
        private readonly Filesystem $filesystem,
    ) {
    }

    public function save(PdfFile $file, string $context = 'default'): void
    {
        $path = $this->resolvePath($file->filename(), $context);
        $this->filesystem->dumpFile($path, $file->content());

        $file->setFullPath($path);
    }

    public function remove(string $filename, string $context = 'default'): void
    {
        $this->filesystem->remove($this->resolvePath($filename, $context));
    }

    public function has(string $filename, string $context = 'default'): bool
    {
        return is_file($this->resolvePath($filename, $context));
    }

    public function get(string $filename, string $context = 'default'): PdfFile
    {
        $path = $this->resolvePath($filename, $context);
        if (!is_file($path)) {
            throw new \InvalidArgumentException(sprintf('PDF file "%s" not found.', $filename));
        }

        $content = file_get_contents($path);
        if (false === $content) {
            throw new \InvalidArgumentException(sprintf('Could not read PDF file "%s".', $filename));
        }

        $file = new PdfFile($filename, $content);
        $file->setFullPath($path);

        return $file;
    }

    private function resolvePath(string $filename, string $context): string
    {
        if (!isset($this->contextDirectories[$context])) {
            throw new \InvalidArgumentException(sprintf('Unknown PDF context "%s".', $context));
        }

        return $this->contextDirectories[$context] . '/' . $filename;
    }
}
