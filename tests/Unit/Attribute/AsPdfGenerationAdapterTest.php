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

namespace Tests\Sylius\PdfGenerationBundle\Unit\Attribute;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sylius\PdfGenerationBundle\Attribute\AsPdfGenerationAdapter;

final class AsPdfGenerationAdapterTest extends TestCase
{
    #[Test]
    public function it_stores_the_adapter_key(): void
    {
        $attribute = new AsPdfGenerationAdapter('my_adapter');

        self::assertSame('my_adapter', $attribute->key);
    }

    #[Test]
    public function it_is_a_target_class_attribute(): void
    {
        $reflection = new \ReflectionClass(AsPdfGenerationAdapter::class);
        $attributes = $reflection->getAttributes(\Attribute::class);

        self::assertCount(1, $attributes);

        /** @var \Attribute $attributeInstance */
        $attributeInstance = $attributes[0]->newInstance();

        self::assertSame(\Attribute::TARGET_CLASS, $attributeInstance->flags);
    }
}
