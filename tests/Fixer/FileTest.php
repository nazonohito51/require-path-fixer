<?php
namespace RequirePathFixer\Fixer;

use PHPUnit\Framework\TestCase;

class FileTest extends TestCase
{
    public function testGetRequireStatements()
    {
        $file = new File(__DIR__ . '/../fixtures/before/View.php');

        $statements = $file->getRequireStatements();

        $this->assertEquals(3, count($statements));
        $this->assertEquals(3, $statements[0]->getIndex());
        $this->assertEquals(realpath(__DIR__ . '/../fixtures/before/conf/config.php'), $statements[0]->getRequiredFilePath());
        $this->assertEquals(15, $statements[1]->getIndex());
        $this->assertEquals(realpath(__DIR__ . '/../fixtures/before/conf/const.php'), $statements[1]->getRequiredFilePath());
        $this->assertEquals(24, $statements[2]->getIndex());
        $this->assertEquals('common/Model.php', $statements[2]->getRequiredFilePath());
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
            $file->fixedRequireStatements(realpath(__DIR__ . '/../../'), 'APP_ROOT')
        );
    }
}
