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

namespace Sylius\PdfGenerationBundle\Core\Processor;

final class CompositeOptionsProcessor implements OptionsProcessorInterface
{
    /** @param array<string, list<OptionsProcessorInterface>> $processors keyed by context name */
    public function __construct(
        private readonly array $processors = [],
    ) {
    }

    public function process(object $generator, string $context = 'default'): void
    {
        $defaults = $this->processors['default'] ?? [];
        $contextSpecific = ('default' !== $context) ? ($this->processors[$context] ?? []) : [];

        foreach (array_merge($defaults, $contextSpecific) as $processor) {
            $processor->process($generator, $context);
        }
    }
}
