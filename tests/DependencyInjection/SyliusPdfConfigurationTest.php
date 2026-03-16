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

namespace Tests\Sylius\PdfBundle\DependencyInjection;

use Matthias\SymfonyConfigTest\PhpUnit\ConfigurationTestCaseTrait;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sylius\PdfBundle\DependencyInjection\Configuration;

final class SyliusPdfConfigurationTest extends TestCase
{
    use ConfigurationTestCaseTrait;

    #[Test]
    public function it_has_knp_snappy_as_default_adapter(): void
    {
        $this->assertProcessedConfigurationEquals(
            [[]],
            ['default' => [
                'adapter' => 'knp_snappy',
                'storage' => [
                    'type' => 'flysystem',
                    'filesystem' => 'default.storage',
                    'prefix' => 'pdf',
                    'directory' => null,
                ],
            ]],
            'default',
        );
    }

    #[Test]
    public function it_allows_custom_contexts_to_be_defined(): void
    {
        $this->assertProcessedConfigurationEquals(
            [['contexts' => [
                'invoice' => ['adapter' => 'dompdf'],
                'coupon' => ['adapter' => 'dompdf'],
            ]]],
            ['contexts' => [
                'invoice' => ['adapter' => 'dompdf'],
                'coupon' => ['adapter' => 'dompdf'],
            ]],
            'contexts',
        );
    }

    #[Test]
    public function it_rejects_context_named_default(): void
    {
        $this->assertConfigurationIsInvalid(
            [['contexts' => ['default' => ['adapter' => 'dompdf']]]],
            'The context name "default" is reserved.',
        );
    }

    #[Test]
    public function it_has_default_storage_config_in_default_block(): void
    {
        $this->assertProcessedConfigurationEquals(
            [[]],
            ['default' => [
                'adapter' => 'knp_snappy',
                'storage' => [
                    'type' => 'flysystem',
                    'filesystem' => 'default.storage',
                    'prefix' => 'pdf',
                    'directory' => null,
                ],
            ]],
            'default',
        );
    }

    #[Test]
    public function it_allows_storage_override_in_default_block(): void
    {
        $this->assertProcessedConfigurationEquals(
            [['default' => ['storage' => [
                'type' => 'filesystem',
                'directory' => '/custom/path',
            ]]]],
            ['default' => [
                'adapter' => 'knp_snappy',
                'storage' => [
                    'type' => 'filesystem',
                    'filesystem' => 'default.storage',
                    'prefix' => 'pdf',
                    'directory' => '/custom/path',
                ],
            ]],
            'default',
        );
    }

    #[Test]
    public function it_allows_storage_per_context(): void
    {
        $this->assertProcessedConfigurationEquals(
            [['contexts' => [
                'invoice' => [
                    'adapter' => 'dompdf',
                    'storage' => [
                        'type' => 'flysystem',
                        'filesystem' => 's3.storage',
                        'prefix' => 'invoices',
                    ],
                ],
            ]]],
            ['contexts' => [
                'invoice' => [
                    'adapter' => 'dompdf',
                    'storage' => [
                        'type' => 'flysystem',
                        'filesystem' => 's3.storage',
                        'prefix' => 'invoices',
                        'directory' => null,
                    ],
                ],
            ]],
            'contexts',
        );
    }

    #[Test]
    public function it_rejects_filesystem_type_without_directory_in_default(): void
    {
        $this->assertConfigurationIsInvalid(
            [['default' => ['storage' => ['type' => 'filesystem']]]],
            'The "directory" option is required when storage type is "filesystem".',
        );
    }

    #[Test]
    public function it_rejects_filesystem_type_without_directory_in_context(): void
    {
        $this->assertConfigurationIsInvalid(
            [['contexts' => ['invoice' => ['storage' => ['type' => 'filesystem']]]]],
            'The "directory" option is required when storage type is "filesystem".',
        );
    }

    #[Test]
    public function it_has_no_storage_in_context_by_default(): void
    {
        $this->assertProcessedConfigurationEquals(
            [['contexts' => ['invoice' => ['adapter' => 'dompdf']]]],
            ['contexts' => ['invoice' => ['adapter' => 'dompdf']]],
            'contexts',
        );
    }

    #[Test]
    public function it_has_empty_contexts_by_default(): void
    {
        $this->assertProcessedConfigurationEquals(
            [[]],
            ['contexts' => []],
            'contexts',
        );
    }

    protected function getConfiguration(): Configuration
    {
        return new Configuration();
    }
}
