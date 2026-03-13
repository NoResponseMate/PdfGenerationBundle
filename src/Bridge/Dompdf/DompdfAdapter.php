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
use Sylius\PdfBundle\Core\Processor\OptionsProcessorInterface;
use Sylius\PdfBundle\Core\Registry\GeneratorProviderRegistryInterface;

final class DompdfAdapter implements PdfGenerationAdapterInterface
{
    public const NAME = 'dompdf';

    public function __construct(
        private readonly GeneratorProviderRegistryInterface $generatorProviderRegistry,
        private readonly OptionsProcessorInterface $optionsProcessor,
        private readonly string $context,
    ) {
    }

    public function generate(string $html): string
    {
        $dompdf = $this->generatorProviderRegistry->get(self::NAME, $this->context);
        if (!$dompdf instanceof Dompdf) {
            throw new \InvalidArgumentException(sprintf('Expected an instance of %s, got %s.', Dompdf::class, get_debug_type($dompdf)));
        }
        $this->optionsProcessor->process($dompdf, $this->context);
        $dompdf->loadHtml($html);
        $dompdf->render();

        return $dompdf->output();
    }
}
