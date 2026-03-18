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

namespace Tests\Sylius\PdfGenerationBundle\Unit\Core\Attribute;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sylius\PdfGenerationBundle\Core\Attribute\AsPdfOptionsProcessor;

final class AsPdfOptionsProcessorTest extends TestCase
{
    #[Test]
    public function it_stores_adapter_with_default_context_and_priority(): void
    {
        $attribute = new AsPdfOptionsProcessor('dompdf');

        self::assertSame('dompdf', $attribute->adapter);
        self::assertSame('default', $attribute->context);
        self::assertSame(0, $attribute->priority);
    }

    #[Test]
    public function it_stores_adapter_with_custom_context_and_priority(): void
    {
        $attribute = new AsPdfOptionsProcessor('knp_snappy', 'invoice', 10);

        self::assertSame('knp_snappy', $attribute->adapter);
        self::assertSame('invoice', $attribute->context);
        self::assertSame(10, $attribute->priority);
    }

    #[Test]
    public function it_is_a_target_class_attribute(): void
    {
        $reflection = new \ReflectionClass(AsPdfOptionsProcessor::class);
        $attributes = $reflection->getAttributes(\Attribute::class);

        self::assertCount(1, $attributes);

        /** @var \Attribute $attributeInstance */
        $attributeInstance = $attributes[0]->newInstance();

        self::assertSame(\Attribute::TARGET_CLASS, $attributeInstance->flags);
    }
}
