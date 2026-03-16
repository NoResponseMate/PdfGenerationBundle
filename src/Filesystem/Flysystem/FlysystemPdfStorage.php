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

namespace Sylius\PdfBundle\Filesystem\Flysystem;

use League\Flysystem\FilesystemOperator;
use League\Flysystem\UnableToReadFile;
use Sylius\PdfBundle\Core\Filesystem\Storage\PdfStorageInterface;
use Sylius\PdfBundle\Core\Model\PdfFile;

final class FlysystemPdfStorage implements PdfStorageInterface
{
    public function __construct(
        private readonly FilesystemOperator $filesystem,
        private readonly string $prefix = '',
    ) {
    }

    public function save(PdfFile $file): void
    {
        $path = $this->resolvePath($file->filename());
        $this->filesystem->write($path, $file->content());
        $file->setStoragePath($path);
    }

    public function remove(string $filename): void
    {
        $this->filesystem->delete($this->resolvePath($filename));
    }

    public function has(string $filename): bool
    {
        return $this->filesystem->fileExists($this->resolvePath($filename));
    }

    public function get(string $filename): PdfFile
    {
        $path = $this->resolvePath($filename);

        try {
            $content = $this->filesystem->read($path);
        } catch (UnableToReadFile) {
            throw new \RuntimeException(sprintf('PDF file "%s" not found.', $filename));
        }

        $file = new PdfFile($filename, $content);
        $file->setStoragePath($path);

        return $file;
    }

    private function resolvePath(string $filename): string
    {
        if ($filename !== basename($filename)) {
            throw new \InvalidArgumentException(sprintf('Invalid PDF filename "%s": directory separators are not allowed.', $filename));
        }

        if ('' === $this->prefix) {
            return $filename;
        }

        return $this->prefix . '/' . $filename;
    }
}
