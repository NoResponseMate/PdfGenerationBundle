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

namespace Sylius\PdfBundle\Core\Filesystem\Storage;

use Sylius\PdfBundle\Core\Model\PdfFile;

interface PdfStorageInterface
{
    public function save(PdfFile $file): void;

    public function remove(string $filename): void;

    public function has(string $filename): bool;

    public function get(string $filename): PdfFile;

    public function resolveLocalPath(string $filename): string;
}
