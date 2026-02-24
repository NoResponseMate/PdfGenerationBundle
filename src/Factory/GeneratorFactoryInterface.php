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

namespace Sylius\PdfBundle\Factory;

interface GeneratorFactoryInterface
{
    /**
     * Creates the underlying PDF generator.
     *
     * @param array<string, mixed> $options Context options from configuration
     */
    public function createGenerator(array $options, string $context): object;

    /**
     * Resolves and transforms raw context options into options ready for the generator.
     *
     * @param array<string, mixed> $options
     *
     * @return array<string, mixed>
     */
    public function resolveOptions(array $options): array;
}
