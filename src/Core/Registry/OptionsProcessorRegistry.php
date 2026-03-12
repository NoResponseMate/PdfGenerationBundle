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

namespace Sylius\PdfBundle\Core\Registry;

use Sylius\PdfBundle\Core\Processor\OptionsProcessorInterface;

final class OptionsProcessorRegistry implements OptionsProcessorRegistryInterface
{
    /** @var array<string, array<string, list<OptionsProcessorInterface>>> */
    private array $processors = [];

    /**
     * @param array<OptionsProcessorInterface> $processors
     */
    public function registerProcessors(string $adapterType, string $context, array $processors): void
    {
        $contextKey = 'default' === $context ? '' : $context;

        $this->processors[$adapterType][$contextKey] = $processors;
    }

    public function process(object $generator, string $adapterType, string $context): void
    {
        $processors = $this->getProcessors($adapterType, $context);

        foreach ($processors as $processor) {
            $processor->process($generator);
        }
    }

    /**
     * @return list<OptionsProcessorInterface>
     */
    private function getProcessors(string $adapterType, string $context): array
    {
        $adapterProcessors = $this->processors[$adapterType] ?? [];

        $defaults = $adapterProcessors[''] ?? [];
        $contextSpecific = ('default' !== $context && '' !== $context) ? ($adapterProcessors[$context] ?? []) : [];

        return array_merge($defaults, $contextSpecific);
    }
}
