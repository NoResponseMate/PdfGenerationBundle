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

namespace Tests\Sylius\PdfGenerationBundle\DependencyInjection;

use Matthias\SymfonyConfigTest\PhpUnit\ConfigurationTestCaseTrait;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sylius\PdfGenerationBundle\DependencyInjection\Configuration;

final class SyliusPdfGenerationConfigurationTest extends TestCase
{
    use ConfigurationTestCaseTrait;

    #[Test]
    public function it_has_knp_snappy_as_default_adapter(): void
    {
        $this->assertProcessedConfigurationEquals(
            [[]],
            ['default' => ['adapter' => 'knp_snappy', 'options' => [], 'pdf_files_directory' => null]],
            'default',
        );
    }

    #[Test]
    public function it_has_empty_options_by_default(): void
    {
        $this->assertProcessedConfigurationEquals(
            [[]],
            ['default' => ['adapter' => 'knp_snappy', 'options' => [], 'pdf_files_directory' => null]],
            'default',
        );
    }

    #[Test]
    public function it_allows_custom_contexts_to_be_defined(): void
    {
        $this->assertProcessedConfigurationEquals(
            [['contexts' => [
                'invoice' => ['adapter' => 'dompdf', 'options' => ['defaultPaperSize' => 'a4']],
                'coupon' => ['adapter' => 'dompdf', 'options' => ['isRemoteEnabled' => true]],
            ]]],
            ['contexts' => [
                'invoice' => ['adapter' => 'dompdf', 'options' => ['defaultPaperSize' => 'a4'], 'pdf_files_directory' => null],
                'coupon' => ['adapter' => 'dompdf', 'options' => ['isRemoteEnabled' => true], 'pdf_files_directory' => null],
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
    public function it_has_default_pdf_files_directory(): void
    {
        $this->assertProcessedConfigurationEquals(
            [[]],
            ['pdf_files_directory' => '%kernel.project_dir%/private/pdf'],
            'pdf_files_directory',
        );
    }

    #[Test]
    public function it_allows_to_define_pdf_files_directory(): void
    {
        $this->assertProcessedConfigurationEquals(
            [['pdf_files_directory' => '/custom/path']],
            ['pdf_files_directory' => '/custom/path'],
            'pdf_files_directory',
        );
    }

    #[Test]
    public function it_has_null_pdf_files_directory_in_default_block_by_default(): void
    {
        $this->assertProcessedConfigurationEquals(
            [[]],
            ['default' => ['adapter' => 'knp_snappy', 'options' => [], 'pdf_files_directory' => null]],
            'default',
        );
    }

    #[Test]
    public function it_has_null_pdf_files_directory_in_context_by_default(): void
    {
        $this->assertProcessedConfigurationEquals(
            [['contexts' => ['invoice' => ['adapter' => 'dompdf']]]],
            ['contexts' => ['invoice' => ['adapter' => 'dompdf', 'options' => [], 'pdf_files_directory' => null]]],
            'contexts',
        );
    }

    #[Test]
    public function it_allows_pdf_files_directory_to_be_set_per_context(): void
    {
        $this->assertProcessedConfigurationEquals(
            [['contexts' => ['invoice' => ['adapter' => 'dompdf', 'pdf_files_directory' => '/custom/invoices']]]],
            ['contexts' => ['invoice' => ['adapter' => 'dompdf', 'options' => [], 'pdf_files_directory' => '/custom/invoices']]],
            'contexts',
        );
    }

    #[Test]
    public function it_allows_pdf_files_directory_to_be_set_in_default_block(): void
    {
        $this->assertProcessedConfigurationEquals(
            [['default' => ['pdf_files_directory' => '/custom/default']]],
            ['default' => ['adapter' => 'knp_snappy', 'options' => [], 'pdf_files_directory' => '/custom/default']],
            'default',
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
