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

namespace Sylius\PdfBundle\Adapter;

use Dompdf\Dompdf;
use Sylius\PdfBundle\Factory\GeneratorFactoryInterface;

final class DompdfAdapter implements PdfGenerationAdapterInterface
{
    /** @param array<string, mixed> $options */
    public function __construct(
        private readonly GeneratorFactoryInterface $factory,
        private readonly array $options,
        private readonly string $context,
    ) {
    }

    public function generate(string $html): string
    {
        /** @var Dompdf $dompdf */
        $dompdf = $this->factory->createGenerator($this->options, $this->context);
        $dompdf->loadHtml($html);
        $dompdf->render();

        return $dompdf->output();
    }
}
