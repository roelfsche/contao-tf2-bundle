<?php

/*
 * This file is part of [package name].
 *
 * (c) John Doe
 *
 * @license LGPL-3.0-or-later
 */

namespace Lumturo\ContaoTF2Bundle\Tests;

use Lumturo\ContaoTF2Bundle\ContaoTF2Bundle;
use PHPUnit\Framework\TestCase;

class ContaoTF2BundleTest extends TestCase
{
    public function testCanBeInstantiated()
    {
        $bundle = new ContaoTF2Bundle();

        $this->assertInstanceOf('Lumturo\ContaoTF2Bundle\ContaoTF2Bundel', $bundle);
    }
}
