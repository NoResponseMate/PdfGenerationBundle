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

namespace Sylius\PdfBundle\Adapter;

use Knp\Snappy\GeneratorInterface;
use Symfony\Component\Config\FileLocatorInterface;

final class KnpSnappyAdapter implements PdfGenerationAdapterInterface
{
    /**
     * @param array<string, mixed> $knpSnappyOptions
     * @param array<string, mixed> $options
     */
    public function __construct(
        private readonly FileLocatorInterface $fileLocator,
        private readonly GeneratorInterface $snappy,
        private readonly array $knpSnappyOptions = [],
        private readonly array $options = [],
    ) {
    }

    public function generate(string $html): string
    {
        return $this->snappy->getOutputFromHtml($html, $this->resolveOptions());
    }

    /** @return array<string, mixed> */
    private function resolveOptions(): array
    {
        $options = $this->knpSnappyOptions;

        /** @var list<string> $allowedFiles */
        $allowedFiles = $this->options['allowed_files'] ?? [];

        if (empty($allowedFiles)) {
            return $options;
        }

        if (!isset($options['allow'])) {
            $options['allow'] = [];
        } elseif (!is_array($options['allow'])) {
            $options['allow'] = [$options['allow']];
        }

        $options['allow'] = array_merge(
            $options['allow'],
            array_map(fn (string $file): string => $this->fileLocator->locate($file), $allowedFiles),
        );

        return $options;
    }
}
