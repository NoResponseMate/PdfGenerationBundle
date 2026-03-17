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

namespace Tests\Sylius\PdfBundle\Functional\Bridge;

use Gotenberg\Gotenberg;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sylius\PdfBundle\Core\Renderer\HtmlToPdfRendererInterface;
use Sylius\PdfBundle\DependencyInjection\SyliusPdfExtension;
use Sylius\PdfBundle\SyliusPdfBundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

final class GotenbergAdapterTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        if (!class_exists(Gotenberg::class)) {
            self::markTestSkipped('gotenberg/gotenberg-php is not installed.');
        }
    }

    #[Test]
    public function it_registers_gotenberg_as_default_adapter(): void
    {
        $container = $this->compileContainer([
            'default' => ['adapter' => 'gotenberg'],
        ]);

        self::assertTrue($container->has('sylius_pdf.renderer.html'));

        /** @var HtmlToPdfRendererInterface $renderer */
        $renderer = $container->get('sylius_pdf.renderer.html');
        self::assertInstanceOf(HtmlToPdfRendererInterface::class, $renderer);
    }

    #[Test]
    public function it_registers_gotenberg_as_context_adapter(): void
    {
        $container = $this->compileContainer([
            'default' => ['adapter' => 'gotenberg'],
            'contexts' => [
                'invoice' => ['adapter' => 'gotenberg'],
            ],
        ]);

        self::assertTrue($container->has('sylius_pdf.renderer.html'));
    }

    #[Test]
    public function it_uses_separate_adapter_instances_per_context(): void
    {
        $container = $this->compileContainer([
            'default' => ['adapter' => 'gotenberg'],
            'contexts' => [
                'invoice' => ['adapter' => 'gotenberg'],
            ],
        ]);

        self::assertTrue($container->has('sylius_pdf.adapter.default'));
        self::assertTrue($container->has('sylius_pdf.adapter.invoice'));
        self::assertNotSame(
            $container->get('sylius_pdf.adapter.default'),
            $container->get('sylius_pdf.adapter.invoice'),
        );
    }

    /**
     * @param array<string, mixed> $config
     */
    private function compileContainer(array $config): ContainerBuilder
    {
        $container = new ContainerBuilder();

        $container->setParameter('kernel.project_dir', sys_get_temp_dir());

        $container->setDefinition('file_locator', new Definition(\Symfony\Component\Config\FileLocator::class, [[]]));

        $bundle = new SyliusPdfBundle();
        $bundle->build($container);

        $extension = new SyliusPdfExtension();
        $extension->load([$config], $container);

        $container->getDefinition('sylius_pdf.renderer.html')->setPublic(true);
        $container->getDefinition('sylius_pdf.adapter.default')->setPublic(true);

        if ($container->hasDefinition('sylius_pdf.adapter.invoice')) {
            $container->getDefinition('sylius_pdf.adapter.invoice')->setPublic(true);
        }

        $container->compile();

        return $container;
    }
}
