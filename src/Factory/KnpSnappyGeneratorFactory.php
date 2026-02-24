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

namespace Sylius\PdfBundle\Factory;

use Knp\Snappy\GeneratorInterface;
use Symfony\Component\Config\FileLocatorInterface;

final class KnpSnappyGeneratorFactory implements GeneratorFactoryInterface
{
    /** @param array<string, mixed> $knpSnappyOptions */
    public function __construct(
        private readonly GeneratorInterface $snappy,
        private readonly FileLocatorInterface $fileLocator,
        private readonly array $knpSnappyOptions = [],
    ) {
    }

    public function createGenerator(array $options, string $context): GeneratorInterface
    {
        return $this->snappy;
    }

    public function resolveOptions(array $options): array
    {
        $merged = array_merge($this->knpSnappyOptions, $options);

        /** @var list<string> $allowedFiles */
        $allowedFiles = $merged['allowed_files'] ?? [];

        if ([] === $allowedFiles) {
            return $merged;
        }

        unset($merged['allowed_files']);

        if (!isset($merged['allow'])) {
            $merged['allow'] = [];
        } elseif (!is_array($merged['allow'])) {
            $merged['allow'] = [$merged['allow']];
        }

        $merged['allow'] = array_merge(
            $merged['allow'],
            array_map(fn (string $file): string => $this->fileLocator->locate($file), $allowedFiles),
        );

        return $merged;
    }
}
