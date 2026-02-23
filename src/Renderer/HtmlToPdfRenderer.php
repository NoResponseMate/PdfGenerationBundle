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

namespace Sylius\PdfGenerationBundle\Renderer;

use Psr\Container\ContainerInterface;
use Sylius\PdfGenerationBundle\Adapter\PdfGenerationAdapterInterface;

final class HtmlToPdfRenderer implements HtmlToPdfRendererInterface
{
    public function __construct(
        private readonly ContainerInterface $adapterLocator,
    ) {
    }

    public function render(string $html, string $context = 'default'): string
    {
        if (!$this->adapterLocator->has($context)) {
            throw new \InvalidArgumentException(sprintf('Unknown PDF generation context "%s".', $context));
        }

        /** @var PdfGenerationAdapterInterface $adapter */
        $adapter = $this->adapterLocator->get($context);

        return $adapter->generate($html);
    }
}
