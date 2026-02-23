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

namespace Sylius\PdfBundle\Generator;

use Sylius\PdfBundle\Manager\PdfFileManagerInterface;
use Sylius\PdfBundle\Model\PdfFile;

final class PdfFileGenerator implements PdfFileGeneratorInterface
{
    public function __construct(
        private readonly PdfFileManagerInterface $pdfFileManager,
    ) {
    }

    public function generate(string $filename, string $content, string $context = 'default'): PdfFile
    {
        $file = new PdfFile($filename, $content);
        $this->pdfFileManager->save($file, $context);

        return $file;
    }
}
