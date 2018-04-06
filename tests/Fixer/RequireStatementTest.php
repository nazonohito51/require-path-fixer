<?php
namespace RequirePathFixer\Fixer;

use PHPUnit\Framework\TestCase;

class RequireStatementTest extends TestCase
{
    public function testString()
    {
        $statement = new RequireStatement(__DIR__ . '/../fixtures/before/View.php', array(
            array(T_REQUIRE_ONCE, 'require_once', 4),
            '(',
            array(T_CONSTANT_ENCAPSED_STRING, '"common/Model.php"', 4),
            ')',
            ';'
        ));

        $this->assertEquals('require_once("common/Model.php");', $statement->string());
    }

    public function testGetRequiredFilePath()
    {
        $statement = new RequireStatement(__DIR__ . '/../before/fixtures/View.php', array(
            array(T_REQUIRE_ONCE, 'require_once', 4),
            '(',
            array(T_CONSTANT_ENCAPSED_STRING, '"common/Model.php"', 4),
            ')',
            ';'
        ));

        $this->assertEquals('common/Model.php', $statement->getRequiredFilePath());
        $this->assertEquals('relative', $statement->type());
    }

    public function testGetRequiredFilePath_UseDir()
    {
        $statement = new RequireStatement(__DIR__ . '/../fixtures/before/View.php', array(
            array(T_REQUIRE_ONCE, 'require_once', 2),
            '(',
            array(T_DIR, '__DIR__', 2),
            array(T_WHITESPACE, ' ', 2),
            '.',
            array(T_WHITESPACE, ' ', 2),
            array(T_CONSTANT_ENCAPSED_STRING, '"/common/Model.php"', 2),
            ')',
            ';'
        ));

        $this->assertEquals(realpath(__DIR__ . '/../fixtures/before/common/Model.php'), $statement->getRequiredFilePath());
        $this->assertEquals('absolute', $statement->type());
    }

    public function testGetRequiredFilePath_UseDirname()
    {
        $statement = new RequireStatement(__DIR__ . '/../fixtures/before/View.php', array(
            array(T_REQUIRE_ONCE, 'require_once', 2),
            array(T_WHITESPACE, ' ', 2),
            array(T_STRING, 'dirname', 2),
            '(',
            array(T_FILE, '__FILE__', 2),
            ')',
            array(T_WHITESPACE, ' ', 2),
            '.',
            array(T_WHITESPACE, ' ', 2),
            array(T_CONSTANT_ENCAPSED_STRING, '"/common/Model.php"', 2),
            array(T_WHITESPACE, ' ', 2),
            ';'
        ));

        $this->assertEquals(realpath(__DIR__ . '/../fixtures/before/common/Model.php'), $statement->getRequiredFilePath());
        $this->assertEquals('absolute', $statement->type());
    }

    public function testGetRequiredFilePath_UseVariable()
    {
        $statement = new RequireStatement(__DIR__ . '/../fixtures/before/View.php', array(
            array(T_REQUIRE_ONCE, 'require_once', 2),
            array(T_WHITESPACE, ' ', 2),
            array(T_VARIABLE, '$hoge', 2),
            array(T_WHITESPACE, ' ', 2),
            '.',
            array(T_WHITESPACE, ' ', 2),
            array(T_CONSTANT_ENCAPSED_STRING, '"Model.php"', 2),
            ';'
        ));

        $this->assertNull($statement->getRequiredFilePath());
        $this->assertEquals('variable', $statement->type());
    }

    public function testGetRequiredFilePath_UseConstant()
    {
        $statement = new RequireStatement(__DIR__ . '/../fixtures/before/View.php', array(
            array(T_REQUIRE_ONCE, 'require_once', 2),
            array(T_WHITESPACE, ' ', 2),
            array(T_VARIABLE, 'SMARTY_DIR', 2),
            array(T_WHITESPACE, ' ', 2),
            '.',
            array(T_WHITESPACE, ' ', 2),
            array(T_CONSTANT_ENCAPSED_STRING, '"Model.php"', 2),
            ';'
        ));

        $this->assertNull($statement->getRequiredFilePath());
        $this->assertEquals('variable', $statement->type());
    }

    public function testGetRequiredFilePath_NoPath()
    {
        $statement = new RequireStatement(__DIR__ . '/../fixtures/before/View.php', array(
            array(T_REQUIRE_ONCE, 'require_once', 2),
            array(T_WHITESPACE, ' ', 2),
            array(T_CLASS, 'class', 2),
            ';'
        ));

        $this->assertNull($statement->getRequiredFilePath());
        $this->assertEquals('unexpected', $statement->type());
    }

    public function testGetFixedStatement()
    {
        $statement = new RequireStatement(__DIR__ . '/../fixtures/before/View.php', array(
            array(T_REQUIRE_ONCE, 'require_once', 2),
            '(',
            array(T_DIR, '__DIR__', 2),
            array(T_WHITESPACE, ' ', 2),
            '.',
            array(T_WHITESPACE, ' ', 2),
            array(T_CONSTANT_ENCAPSED_STRING, '"/common/Model.php"', 2),
            ')',
            ';'
        ));

        $this->assertEquals(
            "require_once APP_ROOT . '/tests/fixtures/before/common/Model.php';",
            $statement->getFixedStatement(realpath(__DIR__ . '/../../'), 'APP_ROOT')
        );
    }

    public function testGetFixedStatement_NoConstant()
    {
        $statement = new RequireStatement(__DIR__ . '/../fixtures/before/View.php', array(
            array(T_REQUIRE_ONCE, 'require_once', 2),
            '(',
            array(T_DIR, '__DIR__', 2),
            array(T_WHITESPACE, ' ', 2),
            '.',
            array(T_WHITESPACE, ' ', 2),
            array(T_CONSTANT_ENCAPSED_STRING, '"/common/Model.php"', 2),
            ')',
            ';'
        ));

        $path = realpath(__DIR__ . '/../../');
        $this->assertEquals(
            "require_once '{$path}' . '/tests/fixtures/before/common/Model.php';",
            $statement->getFixedStatement(realpath(__DIR__ . '/../../'))
        );
    }

    public function testGetFixedStatement_NotAbsolutePath()
    {
        $statement = new RequireStatement(__DIR__ . '/../fixtures/before/View.php', array(
            array(T_REQUIRE_ONCE, 'require_once', 4),
            '(',
            array(T_CONSTANT_ENCAPSED_STRING, '"common/Model.php"', 4),
            ')',
            ';'
        ));

        $this->assertNull($statement->getFixedStatement(realpath(__DIR__ . '/../../'), 'APP_ROOT'));
    }

    public function testGuess()
    {
        $statement = new RequireStatement(__DIR__ . '/../fixtures/before/View.php', array(
            array(T_REQUIRE_ONCE, 'require_once', 4),
            '(',
            array(T_CONSTANT_ENCAPSED_STRING, '"common/Model.php"', 4),
            ')',
            ';'
        ));

        $statement->guess(realpath(__DIR__ . '/../fixtures/before/common/Model.php'));

        $this->assertEquals(
            "require_once APP_ROOT . '/tests/fixtures/before/common/Model.php';",
            $statement->getFixedStatement(realpath(__DIR__ . '/../../'), 'APP_ROOT')
        );
        $this->assertEquals('guess', $statement->type());
    }
}
