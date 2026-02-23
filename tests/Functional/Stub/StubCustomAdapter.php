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

namespace Tests\Sylius\PdfGenerationBundle\Functional\Stub;

use Sylius\PdfGenerationBundle\Adapter\PdfGenerationAdapterInterface;
use Sylius\PdfGenerationBundle\Attribute\AsPdfGenerationAdapter;

#[AsPdfGenerationAdapter('stub_custom')]
final class StubCustomAdapter implements PdfGenerationAdapterInterface
{
    public function generate(string $html): string
    {
        return 'STUB:' . $html;
    }
}
