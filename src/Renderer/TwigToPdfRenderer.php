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

use Twig\Environment;

final class TwigToPdfRenderer implements TwigToPdfRendererInterface
{
    public function __construct(
        private readonly Environment $twig,
        private readonly HtmlToPdfRendererInterface $htmlToPdfRenderer,
    ) {
    }

    /** @param array<string, mixed> $parameters */
    public function render(string $template, array $parameters = [], string $context = 'default'): string
    {
        return $this->htmlToPdfRenderer->render(
            $this->twig->render($template, $parameters),
            $context,
        );
    }
}
