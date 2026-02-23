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

namespace Sylius\PdfGenerationBundle\Manager;

use Sylius\PdfGenerationBundle\Model\PdfFile;

interface PdfFileManagerInterface
{
    public function save(PdfFile $file, string $context = 'default'): void;

    public function remove(string $filename, string $context = 'default'): void;

    public function has(string $filename, string $context = 'default'): bool;

    public function get(string $filename, string $context = 'default'): PdfFile;
}
