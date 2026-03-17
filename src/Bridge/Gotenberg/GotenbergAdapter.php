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

namespace Sylius\PdfBundle\Bridge\Gotenberg;

use Gotenberg\Gotenberg;
use Gotenberg\Modules\ChromiumPdf;
use Gotenberg\Stream;
use Sylius\PdfBundle\Core\Adapter\PdfGenerationAdapterInterface;
use Sylius\PdfBundle\Core\Processor\OptionsProcessorInterface;
use Sylius\PdfBundle\Core\Registry\GeneratorProviderRegistryInterface;

final class GotenbergAdapter implements PdfGenerationAdapterInterface
{
    public const NAME = 'gotenberg';

    public function __construct(
        private readonly GeneratorProviderRegistryInterface $generatorProviderRegistry,
        private readonly OptionsProcessorInterface $optionsProcessor,
        private readonly string $context,
    ) {
    }

    public function generate(string $html): string
    {
        $builder = $this->generatorProviderRegistry->get(self::NAME, $this->context);
        if (!$builder instanceof ChromiumPdf) {
            throw new \InvalidArgumentException(sprintf('Expected an instance of %s, got %s.', ChromiumPdf::class, get_debug_type($builder)));
        }
        $this->optionsProcessor->process($builder, $this->context);
        $request = $builder->html(Stream::string('index.html', $html));

        return Gotenberg::send($request)->getBody()->getContents();
    }
}
