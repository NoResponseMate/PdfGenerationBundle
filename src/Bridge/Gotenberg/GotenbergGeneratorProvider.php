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

namespace Sylius\PdfBundle\Bridge\Gotenberg;

use Gotenberg\Gotenberg;
use Gotenberg\Modules\ChromiumPdf;
use Sylius\PdfBundle\Core\Provider\GeneratorProviderInterface;

final class GotenbergGeneratorProvider implements GeneratorProviderInterface
{
    public function __construct(
        private readonly string $baseUrl,
    ) {
    }

    public function get(string $context = 'default'): ChromiumPdf
    {
        return Gotenberg::chromium($this->baseUrl)->pdf();
    }
}
