<?php
namespace RequirePathFixer\Fixer;

use PHPUnit\Framework\TestCase;

class FileTest extends TestCase
{
    public function testGetRequireStatements()
    {
        $file = new File(__DIR__ . '/../fixtures/before/View.php');

        $statements = $file->getRequireStatements();

        $this->assertEquals(4, count($statements));
        $this->assertEquals(realpath(__DIR__ . '/../fixtures/before/conf/config.php'), $statements[0]->getRequireFile());
        $this->assertEquals(realpath(__DIR__ . '/../fixtures/before/conf/const.php'), $statements[1]->getRequireFile());
        $this->assertEquals('common/Model.php', $statements[2]->getRequireFile());
        $this->assertNull($statements[3]->getRequireFile());
    }

    public function testGetContents_BeforeFix()
    {
        $file = new File(__DIR__ . '/../fixtures/before/View.php');

        $this->assertEquals(file_get_contents(__DIR__ . '/../fixtures/before/View.php'), $file->getContents());
    }

    public function testFixedRequireStatements()
    {
        $file = new File(__DIR__ . '/../fixtures/before/View.php');

        $this->assertEquals(
            file_get_contents(__DIR__ . '/../fixtures/after/View.php'),
            $file->getFixedContents(realpath(__DIR__ . '/../../'), 'APP_ROOT')
        );
    }
}
