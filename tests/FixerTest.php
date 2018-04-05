<?php
namespace RequirePathFixer;

use PHPUnit\Framework\TestCase;

class FixerTest extends TestCase
{
    public function testGetFiles()
    {
        $fixer = new Fixer(__DIR__ . '/fixtures/before');

        $this->assertContains(realpath(__DIR__ . '/fixtures/before/View.php'), $fixer->getFiles());
        $this->assertContains(realpath(__DIR__ . '/fixtures/before/common/Model.php'), $fixer->getFiles());
        $this->assertContains(realpath(__DIR__ . '/fixtures/before/conf/config.php'), $fixer->getFiles());
        $this->assertContains(realpath(__DIR__ . '/fixtures/before/conf/const.php'), $fixer->getFiles());
    }

    public function testReport()
    {
        $fixer = new Fixer(__DIR__ . '/fixtures/before');
        $fixer->report(realpath(__DIR__ . '/../'), 'APP_ROOT');
    }
}
