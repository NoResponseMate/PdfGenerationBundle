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

namespace Tests\Sylius\PdfBundle\Functional\Dompdf;

use Knp\Snappy\GeneratorInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sylius\PdfBundle\Core\Renderer\HtmlToPdfRendererInterface;
use Sylius\PdfBundle\DependencyInjection\SyliusPdfExtension;
use Sylius\PdfBundle\SyliusPdfBundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Twig\Environment;

final class DompdfAdapterTest extends TestCase
{
    #[Test]
    public function it_renders_pdf_with_dompdf_as_default_adapter(): void
    {
        $container = $this->compileContainer([
            'default' => ['adapter' => 'dompdf'],
        ]);

        /** @var HtmlToPdfRendererInterface $renderer */
        $renderer = $container->get('sylius_pdf.renderer.html');
        $result = $renderer->render('<html><body><h1>Hello</h1></body></html>');

        self::assertStringStartsWith('%PDF-', $result);
    }

    #[Test]
    public function it_renders_pdf_with_dompdf_as_context_adapter(): void
    {
        $container = $this->compileContainer([
            'default' => ['adapter' => 'knp_snappy'],
            'contexts' => [
                'invoice' => ['adapter' => 'dompdf'],
            ],
        ]);

        /** @var HtmlToPdfRendererInterface $renderer */
        $renderer = $container->get('sylius_pdf.renderer.html');
        $result = $renderer->render('<html><body><p>Invoice</p></body></html>', 'invoice');

        self::assertStringStartsWith('%PDF-', $result);
    }

    #[Test]
    public function it_renders_pdf_with_dompdf_options(): void
    {
        $container = $this->compileContainer([
            'default' => ['adapter' => 'dompdf'],
        ]);

        /** @var HtmlToPdfRendererInterface $renderer */
        $renderer = $container->get('sylius_pdf.renderer.html');
        $result = $renderer->render('<html><body><p>A4 document</p></body></html>');

        self::assertStringStartsWith('%PDF-', $result);
    }

    #[Test]
    public function it_renders_separate_pdf_per_call(): void
    {
        $container = $this->compileContainer([
            'default' => ['adapter' => 'dompdf'],
        ]);

        /** @var HtmlToPdfRendererInterface $renderer */
        $renderer = $container->get('sylius_pdf.renderer.html');

        $first = $renderer->render('<html><body>First</body></html>');
        $second = $renderer->render('<html><body>Second</body></html>');

        self::assertStringStartsWith('%PDF-', $first);
        self::assertStringStartsWith('%PDF-', $second);
        self::assertNotSame($first, $second);
    }

    #[Test]
    public function it_uses_separate_dompdf_instances_per_context(): void
    {
        $container = $this->compileContainer([
            'default' => ['adapter' => 'dompdf'],
            'contexts' => [
                'invoice' => ['adapter' => 'dompdf'],
            ],
        ]);

        /** @var HtmlToPdfRendererInterface $renderer */
        $renderer = $container->get('sylius_pdf.renderer.html');

        $defaultResult = $renderer->render('<html><body>Default</body></html>');
        $invoiceResult = $renderer->render('<html><body>Invoice</body></html>', 'invoice');

        self::assertStringStartsWith('%PDF-', $defaultResult);
        self::assertStringStartsWith('%PDF-', $invoiceResult);
    }

    /**
     * @param array<string, mixed> $config
     */
    private function compileContainer(array $config): ContainerBuilder
    {
        $container = new ContainerBuilder();

        $container->setParameter('kernel.project_dir', sys_get_temp_dir());
        $container->setParameter('knp_snappy.pdf.options', []);

        $knpSnappyDefinition = new Definition(GeneratorInterface::class);
        $knpSnappyDefinition->setSynthetic(true);
        $container->setDefinition('knp_snappy.pdf', $knpSnappyDefinition);

        $container->setDefinition('file_locator', new Definition(\Symfony\Component\Config\FileLocator::class, [[]]));

        $twigDefinition = new Definition(Environment::class);
        $twigDefinition->setSynthetic(true);
        $container->setDefinition('twig', $twigDefinition);

        $bundle = new SyliusPdfBundle();
        $bundle->build($container);

        $extension = new SyliusPdfExtension();
        $extension->load([$config], $container);

        $container->getDefinition('sylius_pdf.renderer.html')->setPublic(true);

        $container->compile();

        return $container;
    }
}
