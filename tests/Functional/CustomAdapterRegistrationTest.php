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

namespace Tests\Sylius\PdfBundle\Functional;

use Knp\Snappy\GeneratorInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sylius\PdfBundle\Bridge\Dompdf\DompdfAdapter;
use Sylius\PdfBundle\Core\Adapter\PdfGenerationAdapterInterface;
use Sylius\PdfBundle\Core\Renderer\HtmlToPdfRendererInterface;
use Sylius\PdfBundle\DependencyInjection\SyliusPdfExtension;
use Sylius\PdfBundle\SyliusPdfBundle;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Tests\Sylius\PdfBundle\Functional\Stub\StubCustomAdapter;
use Twig\Environment;

final class CustomAdapterRegistrationTest extends TestCase
{
    #[Test]
    public function it_compiles_container_with_built_in_adapters_only(): void
    {
        $container = $this->createContainer([
            'default' => ['adapter' => 'knp_snappy'],
            'contexts' => [
                'invoice' => ['adapter' => 'dompdf'],
            ],
        ]);

        $container->compile();

        self::assertTrue($container->hasAlias(PdfGenerationAdapterInterface::class));
        self::assertSame(
            'sylius_pdf.adapter.default',
            (string) $container->getAlias(PdfGenerationAdapterInterface::class),
        );

        self::assertInstanceOf(
            DompdfAdapter::class,
            $container->get('sylius_pdf.adapter.invoice'),
        );
    }

    #[Test]
    public function it_compiles_container_with_custom_adapter_via_tag(): void
    {
        $container = $this->createContainer([
            'default' => ['adapter' => 'knp_snappy'],
            'contexts' => [
                'invoice' => ['adapter' => 'stub_custom'],
            ],
        ]);

        $stubDefinition = new Definition(StubCustomAdapter::class);
        $stubDefinition->addTag('sylius_pdf.adapter', ['key' => 'stub_custom']);
        $stubDefinition->setPublic(true);
        $container->setDefinition(StubCustomAdapter::class, $stubDefinition);

        $container->compile();

        /** @var HtmlToPdfRendererInterface $renderer */
        $renderer = $container->get('sylius_pdf.renderer.html');
        self::assertSame('STUB:<p>test</p>', $renderer->render('<p>test</p>', 'invoice'));
    }

    #[Test]
    public function it_compiles_container_with_custom_adapter_as_default(): void
    {
        $container = $this->createContainer([
            'default' => ['adapter' => 'stub_custom'],
        ]);

        $stubDefinition = new Definition(StubCustomAdapter::class);
        $stubDefinition->addTag('sylius_pdf.adapter', ['key' => 'stub_custom']);
        $stubDefinition->setPublic(true);
        $container->setDefinition(StubCustomAdapter::class, $stubDefinition);

        $container->compile();

        self::assertTrue($container->hasAlias(PdfGenerationAdapterInterface::class));
        self::assertSame(
            StubCustomAdapter::class,
            (string) $container->getAlias(PdfGenerationAdapterInterface::class),
        );

        /** @var HtmlToPdfRendererInterface $renderer */
        $renderer = $container->get('sylius_pdf.renderer.html');
        self::assertSame('STUB:<p>test</p>', $renderer->render('<p>test</p>'));
    }

    #[Test]
    public function it_compiles_container_with_mixed_built_in_and_custom_adapters(): void
    {
        $container = $this->createContainer([
            'default' => ['adapter' => 'knp_snappy'],
            'contexts' => [
                'invoice' => ['adapter' => 'stub_custom'],
                'coupon' => ['adapter' => 'dompdf'],
            ],
        ]);

        $stubDefinition = new Definition(StubCustomAdapter::class);
        $stubDefinition->addTag('sylius_pdf.adapter', ['key' => 'stub_custom']);
        $stubDefinition->setPublic(true);
        $container->setDefinition(StubCustomAdapter::class, $stubDefinition);

        $container->compile();

        self::assertInstanceOf(
            DompdfAdapter::class,
            $container->get('sylius_pdf.adapter.coupon'),
        );

        /** @var HtmlToPdfRendererInterface $renderer */
        $renderer = $container->get('sylius_pdf.renderer.html');
        self::assertSame('STUB:<p>invoice</p>', $renderer->render('<p>invoice</p>', 'invoice'));
    }

    #[Test]
    public function it_compiles_container_with_custom_adapter_via_attribute_autoconfiguration(): void
    {
        $container = $this->createContainer([
            'default' => ['adapter' => 'knp_snappy'],
            'contexts' => [
                'invoice' => ['adapter' => 'stub_custom'],
            ],
        ]);

        $stubDefinition = new Definition(StubCustomAdapter::class);
        $stubDefinition->setAutoconfigured(true);
        $stubDefinition->setPublic(true);
        $container->setDefinition(StubCustomAdapter::class, $stubDefinition);

        $container->compile();

        /** @var HtmlToPdfRendererInterface $renderer */
        $renderer = $container->get('sylius_pdf.renderer.html');
        self::assertSame('STUB:<p>test</p>', $renderer->render('<p>test</p>', 'invoice'));
    }

    #[Test]
    public function it_throws_during_compilation_when_custom_adapter_is_not_registered(): void
    {
        $container = $this->createContainer([
            'default' => ['adapter' => 'knp_snappy'],
            'contexts' => [
                'invoice' => ['adapter' => 'nonexistent'],
            ],
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The PDF generation adapter "nonexistent" used in context "invoice" is not registered.');

        $container->compile();
    }

    #[Test]
    public function it_renders_pdf_through_compiled_service_locator(): void
    {
        $container = $this->createContainer([
            'default' => ['adapter' => 'stub_custom'],
        ]);

        $stubDefinition = new Definition(StubCustomAdapter::class);
        $stubDefinition->addTag('sylius_pdf.adapter', ['key' => 'stub_custom']);
        $container->setDefinition(StubCustomAdapter::class, $stubDefinition);

        $container->compile();

        /** @var HtmlToPdfRendererInterface $renderer */
        $renderer = $container->get('sylius_pdf.renderer.html');

        self::assertSame('STUB:<h1>Hello</h1>', $renderer->render('<h1>Hello</h1>'));
    }

    /**
     * @param array<string, mixed> $config
     */
    private function createContainer(array $config = []): ContainerBuilder
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

        if ($container->hasDefinition('sylius_pdf.adapter.default')) {
            $container->getDefinition('sylius_pdf.adapter.default')->setPublic(true);
        }

        /** @var array<string, array<string, mixed>> $contexts */
        $contexts = $config['contexts'] ?? [];

        foreach ($contexts as $contextName => $contextConfig) {
            $serviceId = sprintf('sylius_pdf.adapter.%s', (string) $contextName);
            if ($container->hasDefinition($serviceId)) {
                $container->getDefinition($serviceId)->setPublic(true);
            }
        }

        if ($container->hasAlias(PdfGenerationAdapterInterface::class)) {
            $container->getAlias(PdfGenerationAdapterInterface::class)->setPublic(true);
        }

        $this->addMakeServicesPublicPass($container);

        return $container;
    }

    /**
     * Adds a compiler pass that makes test-relevant aliases and definitions public
     * before RemovePrivateAliasesPass strips them. This is needed because aliases
     * created during compilation (e.g. by RegisterPdfGenerationAdaptersPass) cannot
     * be made public before compile() is called.
     */
    private function addMakeServicesPublicPass(ContainerBuilder $container): void
    {
        $container->addCompilerPass(
            new class() implements CompilerPassInterface {
                public function process(ContainerBuilder $container): void
                {
                    if ($container->hasAlias(PdfGenerationAdapterInterface::class)) {
                        $container->getAlias(PdfGenerationAdapterInterface::class)->setPublic(true);
                    }
                }
            },
            PassConfig::TYPE_BEFORE_REMOVING,
        );
    }
}
