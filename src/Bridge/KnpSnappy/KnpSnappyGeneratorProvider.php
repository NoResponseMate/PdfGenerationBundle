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

namespace Sylius\PdfBundle\Bridge\KnpSnappy;

use Knp\Snappy\GeneratorInterface;
use Sylius\PdfBundle\Core\Provider\GeneratorProviderInterface;

final class KnpSnappyGeneratorProvider implements GeneratorProviderInterface
{
    public function __construct(
        private readonly GeneratorInterface $snappy,
    ) {
    }

    public function get(?string $context = null): GeneratorInterface
    {
        return $this->snappy;
    }
}
