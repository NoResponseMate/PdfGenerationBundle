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

namespace Sylius\PdfBundle\Core\Filesystem\Manager;

use Psr\Container\ContainerInterface;
use Sylius\PdfBundle\Core\Filesystem\Storage\PdfStorageInterface;
use Sylius\PdfBundle\Core\Model\PdfFile;

final class PdfFileManager implements PdfFileManagerInterface
{
    public function __construct(
        private readonly ContainerInterface $storageLocator,
    ) {
    }

    public function save(PdfFile $file, string $context = 'default'): void
    {
        $this->getStorage($context)->save($file);
    }

    public function remove(string $filename, string $context = 'default'): void
    {
        $this->getStorage($context)->remove($filename);
    }

    public function has(string $filename, string $context = 'default'): bool
    {
        return $this->getStorage($context)->has($filename);
    }

    public function get(string $filename, string $context = 'default'): PdfFile
    {
        return $this->getStorage($context)->get($filename);
    }

    public function resolveLocalPath(string $filename, string $context = 'default'): string
    {
        return $this->getStorage($context)->resolveLocalPath($filename);
    }

    private function getStorage(string $context): PdfStorageInterface
    {
        if (!$this->storageLocator->has($context)) {
            throw new \InvalidArgumentException(sprintf('Unknown PDF context "%s".', $context));
        }

        /** @var PdfStorageInterface $storage */
        $storage = $this->storageLocator->get($context);

        return $storage;
    }
}
