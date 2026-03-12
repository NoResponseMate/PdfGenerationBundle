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

namespace Sylius\PdfBundle\Core\Attribute;

#[\Attribute(\Attribute::TARGET_CLASS)]
final readonly class AsPdfGeneratorProvider
{
    public function __construct(
        public string $key,
        public ?string $context = null,
    ) {
    }
}
