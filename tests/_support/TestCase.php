<?php

declare(strict_types=1);

namespace Tests\Support;

use CodeIgniter\Test\CIUnitTestCase;

abstract class TestCase extends CIUnitTestCase
{
    /**
     * Asserts if two strings are equal ignoring line endings.
     */
    protected function assertEqualsIgnoringLE(string $expected, string $actual, string $message = ''): void
    {
        $expected = str_replace("\r\n", "\n", $expected);
        $actual   = str_replace("\r\n", "\n", $actual);

        $this->assertSame($expected, $actual, $message);
    }
}
