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

namespace Sylius\PdfBundle\Core\Renderer;

use Sylius\PdfBundle\Core\Adapter\PdfGenerationAdapterInterface;
use Symfony\Component\DependencyInjection\ServiceLocator;

final class HtmlToPdfRenderer implements HtmlToPdfRendererInterface
{
    /** @param ServiceLocator<PdfGenerationAdapterInterface> $adapterLocator */
    public function __construct(
        private readonly ServiceLocator $adapterLocator,
    ) {
    }

    public function render(string $html, string $context = 'default'): string
    {
        if (!$this->adapterLocator->has($context)) {
            throw new \InvalidArgumentException(sprintf('Unknown PDF generation context "%s".', $context));
        }

        $adapter = $this->adapterLocator->get($context);
        if (!$adapter instanceof PdfGenerationAdapterInterface) {
            throw new \InvalidArgumentException(sprintf('Expected an instance of %s, got %s.', PdfGenerationAdapterInterface::class, get_debug_type($adapter)));
        }

        return $adapter->generate($html);
    }
}
