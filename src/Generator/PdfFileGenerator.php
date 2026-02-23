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

namespace Sylius\PdfGenerationBundle\Generator;

use Sylius\PdfGenerationBundle\Manager\PdfFileManagerInterface;
use Sylius\PdfGenerationBundle\Model\PdfFile;

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
