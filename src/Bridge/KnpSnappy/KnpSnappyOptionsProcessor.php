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
use Sylius\PdfBundle\Core\Processor\OptionsProcessorInterface;
use Symfony\Component\Config\FileLocatorInterface;

final class KnpSnappyOptionsProcessor implements OptionsProcessorInterface
{
    /**
     * @param array<string, mixed> $knpSnappyOptions
     * @param list<string> $allowedFiles
     */
    public function __construct(
        private readonly FileLocatorInterface $fileLocator,
        private readonly array $knpSnappyOptions,
        private readonly array $allowedFiles = [],
    ) {
    }

    public function process(object $generator): void
    {
        /** @var AbstractGenerator $generator */
        $generator->setOptions($this->knpSnappyOptions);

        if ([] === $this->allowedFiles) {
            return;
        }

        $generator->setOption(
            'allow',
            array_map(
                fn (string $file): string => $this->fileLocator->locate($file),
                $this->allowedFiles,
            ),
        );
    }
}
