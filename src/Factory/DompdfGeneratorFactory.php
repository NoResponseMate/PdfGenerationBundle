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

namespace Sylius\PdfBundle\Factory;

use Dompdf\Dompdf;
use Dompdf\Options;

final class DompdfGeneratorFactory implements GeneratorFactoryInterface
{
    public function createGenerator(array $options, string $context): Dompdf
    {
        return new Dompdf(new Options($this->resolveOptions($options)));
    }

    public function resolveOptions(array $options): array
    {
        return $options;
    }
}
