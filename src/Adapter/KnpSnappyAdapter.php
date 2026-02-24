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

use Knp\Snappy\GeneratorInterface;
use Sylius\PdfBundle\Factory\GeneratorFactoryInterface;

final class KnpSnappyAdapter implements PdfGenerationAdapterInterface
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
        /** @var GeneratorInterface $generator */
        $generator = $this->factory->createGenerator($this->options, $this->context);
        $resolvedOptions = $this->factory->resolveOptions($this->options);

        return $generator->getOutputFromHtml($html, $resolvedOptions);
    }
}
