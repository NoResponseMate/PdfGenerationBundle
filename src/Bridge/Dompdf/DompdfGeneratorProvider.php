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
use Sylius\PdfBundle\Core\Provider\GeneratorProviderInterface;

final class DompdfGeneratorProvider implements GeneratorProviderInterface
{
    public function get(string $context = 'default'): Dompdf
    {
        return new Dompdf();
    }
}
