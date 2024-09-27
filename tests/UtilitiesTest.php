<?php

namespace App\Tests;

use App\Utilities;
use PHPUnit\Framework\TestCase;

class UtilitiesTest extends TestCase
{
    public function testaFormatacaoBytes(): void
    {
        $value = Utilities::formatBytes(1000000);
        $this->assertEquals("1MB", $value);
    }
}
