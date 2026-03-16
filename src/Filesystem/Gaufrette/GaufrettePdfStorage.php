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

namespace Sylius\PdfBundle\Filesystem\Gaufrette;

use Gaufrette\Exception\FileNotFound;
use Gaufrette\Filesystem;
use Sylius\PdfBundle\Core\Filesystem\Storage\PdfStorageInterface;
use Sylius\PdfBundle\Core\Model\PdfFile;
use Symfony\Component\Filesystem\Filesystem as LocalFilesystem;

final class GaufrettePdfStorage implements PdfStorageInterface
{
    public function __construct(
        private readonly Filesystem $filesystem,
        private readonly LocalFilesystem $localFilesystem,
        private readonly string $prefix = '',
        private readonly ?string $localCacheDirectory = null,
    ) {
    }

    public function save(PdfFile $file): void
    {
        $path = $this->resolvePath($file->filename());
        $this->filesystem->write($path, $file->content(), true);
        $file->setStoragePath($path);

        $this->invalidateLocalCache($file->filename());
    }

    public function remove(string $filename): void
    {
        $this->filesystem->delete($this->resolvePath($filename));

        $this->invalidateLocalCache($filename);
    }

    public function has(string $filename): bool
    {
        return $this->filesystem->has($this->resolvePath($filename));
    }

    public function get(string $filename): PdfFile
    {
        $path = $this->resolvePath($filename);

        try {
            $content = $this->filesystem->read($path);
        } catch (FileNotFound) {
            throw new \RuntimeException(sprintf('PDF file "%s" not found.', $filename));
        }

        $file = new PdfFile($filename, $content);
        $file->setStoragePath($path);

        return $file;
    }

    public function resolveLocalPath(string $filename): string
    {
        if (null === $this->localCacheDirectory) {
            throw new \LogicException('The "local_cache_directory" option must be configured to use resolveLocalPath() with Gaufrette storage.');
        }

        $localPath = $this->resolveLocalCachePath($filename);

        if (!is_file($localPath)) {
            $pdfFile = $this->get($filename);
            $this->localFilesystem->dumpFile($localPath, $pdfFile->content());
        }

        return $localPath;
    }

    private function invalidateLocalCache(string $filename): void
    {
        if (null === $this->localCacheDirectory) {
            return;
        }

        $localPath = $this->resolveLocalCachePath($filename);

        if (is_file($localPath)) {
            $this->localFilesystem->remove($localPath);
        }
    }

    private function resolveLocalCachePath(string $filename): string
    {
        if ($filename !== basename($filename)) {
            throw new \InvalidArgumentException(sprintf('Invalid PDF filename "%s": directory separators are not allowed.', $filename));
        }

        /** @var string $localCacheDirectory */
        $localCacheDirectory = $this->localCacheDirectory;

        return rtrim($localCacheDirectory, \DIRECTORY_SEPARATOR) . \DIRECTORY_SEPARATOR . $filename;
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
