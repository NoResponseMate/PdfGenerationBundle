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

namespace Sylius\PdfBundle\Renderer;

interface TwigToPdfRendererInterface
{
    /** @param array<string, mixed> $parameters */
    public function render(string $template, array $parameters = [], string $context = 'default'): string;
}
