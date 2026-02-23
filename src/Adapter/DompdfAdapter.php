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

namespace Sylius\PdfGenerationBundle\Adapter;

use Dompdf\Dompdf;
use Dompdf\Options;

final class DompdfAdapter implements PdfGenerationAdapterInterface
{
    private readonly Options $dompdfOptions;

    /** @param array<string, mixed> $options */
    public function __construct(array $options = [])
    {
        $this->dompdfOptions = new Options($options);
    }

    public function generate(string $html): string
    {
        $dompdf = new Dompdf($this->dompdfOptions);
        $dompdf->loadHtml($html);
        $dompdf->render();

        return $dompdf->output();
    }
}
