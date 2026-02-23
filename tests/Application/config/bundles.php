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

use Knp\Bundle\SnappyBundle\KnpSnappyBundle;
use Sylius\PdfGenerationBundle\SyliusPdfGenerationBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;

$bundles = [
    FrameworkBundle::class => ['all' => true],
    SyliusPdfGenerationBundle::class => ['all' => true],
];

if (class_exists(KnpSnappyBundle::class)) {
    $bundles[KnpSnappyBundle::class] = ['test_snappy' => true];
}

return $bundles;
