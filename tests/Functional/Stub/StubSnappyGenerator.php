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

namespace Tests\Sylius\PdfGenerationBundle\Functional\Stub;

use Knp\Snappy\AbstractGenerator;

/**
 * Stub that replaces the real wkhtmltopdf-backed generator in tests.
 * Extends AbstractGenerator so it passes instanceof checks in KnpSnappyOptionsProcessor.
 */
final class StubSnappyGenerator extends AbstractGenerator
{
    public function __construct()
    {
        parent::__construct('/usr/bin/true');
    }

    protected function configure(): void
    {
    }

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
}
