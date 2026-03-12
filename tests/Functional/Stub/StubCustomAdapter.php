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

namespace Tests\Sylius\PdfBundle\Functional\Stub;

use Sylius\PdfBundle\Core\Adapter\PdfGenerationAdapterInterface;
use Sylius\PdfBundle\Core\Attribute\AsPdfGenerationAdapter;

#[AsPdfGenerationAdapter('stub_custom')]
final class StubCustomAdapter implements PdfGenerationAdapterInterface
{
    public function generate(string $html): string
    {
        return 'STUB:' . $html;
    }
}
