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

use Knp\Bundle\GaufretteBundle\KnpGaufretteBundle;
use Knp\Bundle\SnappyBundle\KnpSnappyBundle;
use League\FlysystemBundle\FlysystemBundle;
use Sylius\PdfGenerationBundle\SyliusPdfGenerationBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;

$bundles = [
    FrameworkBundle::class => ['all' => true],
    SyliusPdfGenerationBundle::class => ['all' => true],
    TwigBundle::class => ['all' => true],
    FlysystemBundle::class => ['test_flysystem' => true, 'test_contexts' => true],
];

if (class_exists(KnpSnappyBundle::class)) {
    $bundles[KnpSnappyBundle::class] = ['test_snappy' => true];
}

if (class_exists(KnpGaufretteBundle::class)) {
    $bundles[KnpGaufretteBundle::class] = ['test_gaufrette' => true];
}

return $bundles;
