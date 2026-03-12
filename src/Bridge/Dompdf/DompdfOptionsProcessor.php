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
use Dompdf\Options;
use Sylius\PdfBundle\Core\Processor\OptionsProcessorInterface;

final class DompdfOptionsProcessor implements OptionsProcessorInterface
{
    /** @param array<string, mixed> $options */
    public function __construct(
        private readonly array $options = [],
    ) {
    }

    public function process(object $generator, string $context = 'default'): void
    {
        if ([] === $this->options) {
            return;
        }

        /** @var Dompdf $generator */
        $generator->setOptions(new Options($this->options));
    }
}
