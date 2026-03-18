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

namespace Sylius\PdfGenerationBundle\Core\Registry;

use Sylius\PdfGenerationBundle\Core\Provider\GeneratorProviderInterface;
use Symfony\Component\DependencyInjection\ServiceLocator;

final class GeneratorProviderRegistry implements GeneratorProviderRegistryInterface
{
    /** @param ServiceLocator<GeneratorProviderInterface> $providers */
    public function __construct(
        private readonly ServiceLocator $providers,
    ) {
    }

    public function get(string $adapterType, string $context = 'default'): object
    {
        $contextKey = $adapterType . '.' . $context;

        if ('default' !== $context && $this->providers->has($contextKey)) {
            $provider = $this->providers->get($contextKey);

            return $provider->get($context);
        }

        if ($this->providers->has($adapterType)) {
            $provider = $this->providers->get($adapterType);

            return $provider->get($context);
        }

        throw new \InvalidArgumentException(sprintf(
            'No generator provider registered for adapter type "%s" (context: "%s").',
            $adapterType,
            $context,
        ));
    }
}
