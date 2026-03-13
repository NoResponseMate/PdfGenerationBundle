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

namespace Tests\Sylius\PdfBundle\Functional\Stub;

use Knp\Snappy\GeneratorInterface;

/**
 * Stub that replaces the real wkhtmltopdf-backed generator in tests.
 * Implements GeneratorInterface and adds setOptions/setOption methods
 * needed by KnpSnappyOptionsProcessor (which casts to AbstractGenerator).
 */
final class StubSnappyGenerator implements GeneratorInterface
{
    /**
     * @param array<string>|string $input
     * @param array<string, mixed> $options
     */
    public function generate($input, $output, array $options = [], $overwrite = false): void
    {
    }

    /**
     * @param array<string>|string $html
     * @param array<string, mixed> $options
     */
    public function generateFromHtml($html, $output, array $options = [], $overwrite = false): void
    {
    }

    /**
     * @param array<string>|string $input
     * @param array<string, mixed> $options
     */
    public function getOutput($input, array $options = []): string
    {
        return '%PDF-1.4 stub';
    }

    /**
     * @param array<string>|string $html
     * @param array<string, mixed> $options
     */
    public function getOutputFromHtml($html, array $options = []): string
    {
        return '%PDF-1.4 stub ' . md5(is_array($html) ? implode('', $html) : $html);
    }

    /** @param array<string, mixed> $options */
    public function setOptions(array $options): void
    {
    }

    public function setOption(string $name, mixed $value): void
    {
    }
}
