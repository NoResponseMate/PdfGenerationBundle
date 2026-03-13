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

namespace Sylius\PdfBundle\Bridge\KnpSnappy;

use Knp\Snappy\AbstractGenerator;
use Sylius\PdfBundle\Core\Adapter\PdfGenerationAdapterInterface;
use Sylius\PdfBundle\Core\Processor\OptionsProcessorInterface;
use Sylius\PdfBundle\Core\Registry\GeneratorProviderRegistryInterface;

final class KnpSnappyAdapter implements PdfGenerationAdapterInterface
{
    public const NAME = 'knp_snappy';

    public function __construct(
        private readonly GeneratorProviderRegistryInterface $generatorProviderRegistry,
        private readonly OptionsProcessorInterface $optionsProcessor,
        private readonly string $context,
    ) {
    }

    public function generate(string $html): string
    {
        $generator = $this->generatorProviderRegistry->get(self::NAME, $this->context);
        if (!$generator instanceof AbstractGenerator) {
            throw new \InvalidArgumentException(sprintf('Expected an instance of %s, got %s.', AbstractGenerator::class, get_debug_type($generator)));
        }
        $this->optionsProcessor->process($generator, $this->context);

        return $generator->getOutputFromHtml($html);
    }
}
