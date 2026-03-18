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

namespace Sylius\PdfGenerationBundle\Adapter\KnpSnappy;

use Knp\Snappy\AbstractGenerator;
use Sylius\PdfGenerationBundle\Core\Processor\OptionsProcessorInterface;

final class KnpSnappyOptionsProcessor implements OptionsProcessorInterface
{
    /** @param array<string, mixed> $knpSnappyOptions */
    public function __construct(
        private readonly array $knpSnappyOptions,
    ) {
    }

    public function process(object $generator, string $context = 'default'): void
    {
        if (!$generator instanceof AbstractGenerator) {
            throw new \InvalidArgumentException(sprintf('Expected an instance of %s, got %s.', AbstractGenerator::class, get_debug_type($generator)));
        }

        $generator->setOptions($this->knpSnappyOptions);
    }
}
