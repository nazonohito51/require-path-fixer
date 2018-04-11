<?php
namespace RequirePathFixer\Fixer;

use PHPUnit\Framework\TestCase;

class PhpFileTest extends TestCase
{
    public function testGetRequireStatements()
    {
        $file = new PhpFile(__DIR__ . '/../fixtures/before/View.php');

        $statements = $file->getRequireStatements();

        $this->assertEquals(6, count($statements));
        $this->assertEquals(realpath(__DIR__ . '/../fixtures/before/conf/config.php'), $statements[0]->getRequireFile());
        $this->assertEquals(realpath(__DIR__ . '/../fixtures/before/conf/const.php'), $statements[1]->getRequireFile());
        $this->assertEquals('common/Model.php', $statements[2]->getRequireFile());
        $this->assertEquals('./common/Model.php', $statements[3]->getRequireFile());
        $this->assertNull($statements[4]->getRequireFile());
        $this->assertNull($statements[5]->getRequireFile());
    }

    public function testGetRequireStatements_AddReplacement()
    {
        $file = new PhpFile(__DIR__ . '/../fixtures/before/View.php', array(
            'COMMON_DIR' => realpath(__DIR__ . '/../fixtures/before/common/') . '/'
        ));

        $statements = $file->getRequireStatements();

        $this->assertEquals(6, count($statements));
        $this->assertEquals(realpath(__DIR__ . '/../fixtures/before/conf/config.php'), $statements[0]->getRequireFile());
        $this->assertEquals(realpath(__DIR__ . '/../fixtures/before/conf/const.php'), $statements[1]->getRequireFile());
        $this->assertEquals('common/Model.php', $statements[2]->getRequireFile());
        $this->assertEquals('./common/Model.php', $statements[3]->getRequireFile());
        $this->assertNull($statements[4]->getRequireFile());
        $this->assertEquals(realpath(__DIR__ . '/../fixtures/before/common/Model.php'), $statements[5]->getRequireFile());
    }

    public function testGetContents()
    {
        $file = new PhpFile(__DIR__ . '/../fixtures/before/View.php');

        $this->assertEquals(file_get_contents(__DIR__ . '/../fixtures/before/View.php'), $file->getContents());
    }

    public function testGetFixedContents()
    {
        $file = new PhpFile(__DIR__ . '/../fixtures/before/View.php');

        $this->assertEquals(
            file_get_contents(__DIR__ . '/../fixtures/after/View.php'),
            $file->getFixedContents(realpath(__DIR__ . '/../../'), 'APP_ROOT')
        );
    }
}
