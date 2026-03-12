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

interface OptionsProcessorRegistryInterface
{
    /**
     * Processes the generator by running all registered processors for the given adapter type and context.
     */
    public function process(object $generator, string $adapterType, string $context): void;
}
