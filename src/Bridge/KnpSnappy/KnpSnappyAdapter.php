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

use Knp\Snappy\GeneratorInterface;
use Sylius\PdfBundle\Core\Adapter\PdfGenerationAdapterInterface;
use Sylius\PdfBundle\Core\Registry\GeneratorProviderRegistryInterface;
use Sylius\PdfBundle\Core\Registry\OptionsProcessorRegistryInterface;

final class KnpSnappyAdapter implements PdfGenerationAdapterInterface
{
    public const NAME = 'knp_snappy';

    public function __construct(
        private readonly GeneratorProviderRegistryInterface $generatorProviderRegistry,
        private readonly OptionsProcessorRegistryInterface $processorRegistry,
        private readonly string $context,
    ) {
    }

    public function generate(string $html): string
    {
        /** @var GeneratorInterface $generator */
        $generator = $this->generatorProviderRegistry->get(self::NAME, $this->context);
        $this->processorRegistry->process($generator, self::NAME, $this->context);

        return $generator->getOutputFromHtml($html);
    }
}
