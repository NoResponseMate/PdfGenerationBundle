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

namespace Sylius\PdfGenerationBundle\Adapter\KnpSnappy;

use Knp\Snappy\AbstractGenerator;
use Sylius\PdfGenerationBundle\Core\Provider\GeneratorProviderInterface;

final class KnpSnappyGeneratorProvider implements GeneratorProviderInterface
{
    /** @var \Closure(): AbstractGenerator */
    private readonly \Closure $snappyFactory;

    /**
     * @param \Closure(): AbstractGenerator $snappyFactory
     */
    public function __construct(
        \Closure $snappyFactory,
    ) {
        $this->snappyFactory = $snappyFactory;
    }

    public function get(string $context = 'default'): AbstractGenerator
    {
        return ($this->snappyFactory)();
    }
}
