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

namespace Sylius\PdfBundle\Bridge\Dompdf;

use Dompdf\Dompdf;
use Sylius\PdfBundle\Core\Adapter\PdfGenerationAdapterInterface;
use Sylius\PdfBundle\Core\Registry\GeneratorProviderRegistryInterface;
use Sylius\PdfBundle\Core\Registry\OptionsProcessorRegistryInterface;

final class DompdfAdapter implements PdfGenerationAdapterInterface
{
    public const NAME = 'dompdf';

    public function __construct(
        private readonly GeneratorProviderRegistryInterface $generatorProviderRegistry,
        private readonly OptionsProcessorRegistryInterface $processorRegistry,
        private readonly string $context,
    ) {
    }

    public function generate(string $html): string
    {
        /** @var Dompdf $dompdf */
        $dompdf = $this->generatorProviderRegistry->get(self::NAME, $this->context);
        $this->processorRegistry->process($dompdf, self::NAME, $this->context);
        $dompdf->loadHtml($html);
        $dompdf->render();

        return $dompdf->output();
    }
}
