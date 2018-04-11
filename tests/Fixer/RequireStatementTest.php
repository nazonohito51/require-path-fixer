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
        $this->assertFalse($statement->isIncludeStatement());
    }

    public function testGetRequireFile()
    {
        $statement = new RequireStatement(__DIR__ . '/../before/fixtures/View.php', array(
            array(T_REQUIRE_ONCE, 'require_once', 4),
            '(',
            array(T_CONSTANT_ENCAPSED_STRING, '"common/Model.php"', 4),
            ')',
            ';'
        ));

        $this->assertEquals('common/Model.php', $statement->getRequireFile());
        $this->assertEquals('relative', $statement->type());
    }

    public function testGetRequireFile_UseDir()
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

        $this->assertEquals(realpath(__DIR__ . '/../fixtures/before/common/Model.php'), $statement->getRequireFile());
        $this->assertEquals('absolute', $statement->type());
    }

    public function testGetRequireFile_UseDirname()
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

        $this->assertEquals(realpath(__DIR__ . '/../fixtures/before/common/Model.php'), $statement->getRequireFile());
        $this->assertEquals('absolute', $statement->type());
    }

    public function testGetRequireFile_UseVariable()
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

        $this->assertNull($statement->getRequireFile());
        $this->assertEquals('variable', $statement->type());
    }

    public function testGetRequireFile_UseConstant()
    {
        $statement = new RequireStatement(__DIR__ . '/../fixtures/before/View.php', array(
            array(T_REQUIRE_ONCE, 'require_once', 2),
            array(T_WHITESPACE, ' ', 2),
            array(T_VARIABLE, 'COMMON_DIR', 2),
            array(T_WHITESPACE, ' ', 2),
            '.',
            array(T_WHITESPACE, ' ', 2),
            array(T_CONSTANT_ENCAPSED_STRING, '"Model.php"', 2),
            ';'
        ));

        $this->assertNull($statement->getRequireFile());
        $this->assertEquals('variable', $statement->type());
    }

    public function testGetRequireFile_UseComplexVariableParsedSyntax_CurlyOpenPattern()
    {
        $statement = new RequireStatement(__DIR__ . '/../fixtures/before/View.php', array(
            array(T_REQUIRE_ONCE, 'require_once', 2),
            array(T_WHITESPACE, ' ', 2),
            '"',
            array(T_ENCAPSED_AND_WHITESPACE, 'Controllers/', 2),
            array(T_CURLY_OPEN, '{', 2),
            array(T_VARIABLE, '$controller', 2),
            '}',
            array(T_ENCAPSED_AND_WHITESPACE, '.php', 2),
            '"',
            ';'
        ));

        $this->assertNull($statement->getRequireFile());
        $this->assertEquals('variable', $statement->type());
    }

    public function testGetRequireFile_UseComplexVariableParsedSyntax_DollarOpenCurlyPattern()
    {
        $statement = new RequireStatement(__DIR__ . '/../fixtures/before/View.php', array(
            array(T_REQUIRE_ONCE, 'require_once', 2),
            array(T_WHITESPACE, ' ', 2),
            '"',
            array(T_ENCAPSED_AND_WHITESPACE, 'Controllers/', 2),
            array(T_DOLLAR_OPEN_CURLY_BRACES, '${', 2),
            array(T_STRING_VARNAME, 'controller', 2),
            '}',
            array(T_ENCAPSED_AND_WHITESPACE, '.php', 2),
            '"',
            ';'
        ));

        $this->assertNull($statement->getRequireFile());
        $this->assertEquals('variable', $statement->type());
    }

    public function testGetRequireFile_AddReplacement()
    {
        $statement = new RequireStatement(__DIR__ . '/../fixtures/before/View.php', array(
            array(T_REQUIRE_ONCE, 'require_once', 2),
            array(T_WHITESPACE, ' ', 2),
            array(T_VARIABLE, 'COMMON_DIR', 2),
            array(T_WHITESPACE, ' ', 2),
            '.',
            array(T_WHITESPACE, ' ', 2),
            array(T_CONSTANT_ENCAPSED_STRING, '"Model.php"', 2),
            ';'
        ), array(
            'COMMON_DIR' => realpath(__DIR__ . '/../fixtures/before/common/') . '/'
        ));

        $this->assertEquals(realpath(__DIR__ . '/../fixtures/before/common/Model.php'), $statement->getRequireFile());
        $this->assertEquals('absolute', $statement->type());
    }

    public function testGetRequireFile_AddReplacement_UseComplexVariableParsedSyntax_CurlyOpenPattern()
    {
        $statement = new RequireStatement(__DIR__ . '/../fixtures/before/View.php', array(
            array(T_REQUIRE_ONCE, 'require_once', 2),
            array(T_WHITESPACE, ' ', 2),
            '"',
            array(T_ENCAPSED_AND_WHITESPACE, 'Controllers/', 2),
            array(T_CURLY_OPEN, '{', 2),
            array(T_VARIABLE, '$controller', 2),
            '}',
            array(T_ENCAPSED_AND_WHITESPACE, '.php', 2),
            '"',
            ';'
        ), array(
            '$controller' => 'UserController'
        ));

        $this->assertEquals('Controllers/UserController.php', $statement->getRequireFile());
        $this->assertEquals('relative', $statement->type());
    }

    public function testGetRequireFile_AddReplacement_UseComplexVariableParsedSyntax_DollarOpenCurlyPattern()
    {
        $statement = new RequireStatement(__DIR__ . '/../fixtures/before/View.php', array(
            array(T_REQUIRE_ONCE, 'require_once', 2),
            array(T_WHITESPACE, ' ', 2),
            '"',
            array(T_ENCAPSED_AND_WHITESPACE, 'Controllers/', 2),
            array(T_DOLLAR_OPEN_CURLY_BRACES, '${', 2),
            array(T_STRING_VARNAME, 'controller', 2),
            '}',
            array(T_ENCAPSED_AND_WHITESPACE, '.php', 2),
            '"',
            ';'
        ), array(
            '$controller' => 'UserController'
        ));

        $this->assertEquals('Controllers/UserController.php', $statement->getRequireFile());
        $this->assertEquals('relative', $statement->type());
    }

    public function testGetRequireFile_NoPath()
    {
        $statement = new RequireStatement(__DIR__ . '/../fixtures/before/View.php', array(
            array(T_REQUIRE_ONCE, 'require_once', 2),
            array(T_WHITESPACE, ' ', 2),
            ';'
        ));

        $this->assertNull($statement->getRequireFile());
        $this->assertEquals('unexpected', $statement->type());
    }

    public function testGetFixedString()
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
            $statement->getFixedString(realpath(__DIR__ . '/../../'), 'APP_ROOT')
        );
    }

    public function testGetFixedString_NoConstant()
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
            $statement->getFixedString(realpath(__DIR__ . '/../../'))
        );
    }

    public function testGetFixedString_NotAbsolutePath()
    {
        $statement = new RequireStatement(__DIR__ . '/../fixtures/before/View.php', array(
            array(T_REQUIRE_ONCE, 'require_once', 4),
            '(',
            array(T_CONSTANT_ENCAPSED_STRING, '"common/Model.php"', 4),
            ')',
            ';'
        ));

        $this->assertNull($statement->getFixedString(realpath(__DIR__ . '/../../'), 'APP_ROOT'));
    }

    public function testGuessFromUnique()
    {
        $statement = new RequireStatement(__DIR__ . '/../fixtures/before/View.php', array(
            array(T_REQUIRE_ONCE, 'require_once', 4),
            '(',
            array(T_CONSTANT_ENCAPSED_STRING, '"common/Model.php"', 4),
            ')',
            ';'
        ));

        $statement->guessFromUnique(realpath(__DIR__ . '/../fixtures/before/common/Model.php'));

        $this->assertEquals(
            "require_once APP_ROOT . '/tests/fixtures/before/common/Model.php';",
            $statement->getFixedString(realpath(__DIR__ . '/../../'), 'APP_ROOT')
        );
        $this->assertEquals('unique', $statement->type());
    }

    public function testGuessFromWorkingDir()
    {
        $statement = new RequireStatement(__DIR__ . '/../fixtures/before/View.php', array(
            array(T_REQUIRE_ONCE, 'require_once', 4),
            '(',
            array(T_CONSTANT_ENCAPSED_STRING, '"common/Model.php"', 4),
            ')',
            ';'
        ));

        $statement->guessFromWorkingDir(realpath(__DIR__ . '/../fixtures/before/common/Model.php'));

        $this->assertEquals(
            "require_once APP_ROOT . '/tests/fixtures/before/common/Model.php';",
            $statement->getFixedString(realpath(__DIR__ . '/../../'), 'APP_ROOT')
        );
        $this->assertEquals('working_dir', $statement->type());
    }

    public function testGuessFromIncludePath()
    {
        $statement = new RequireStatement(__DIR__ . '/../fixtures/before/View.php', array(
            array(T_REQUIRE_ONCE, 'require_once', 4),
            '(',
            array(T_CONSTANT_ENCAPSED_STRING, '"common/Model.php"', 4),
            ')',
            ';'
        ));

        $statement->guessFromIncludePath(realpath(__DIR__ . '/../fixtures/before/common/Model.php'));

        $this->assertEquals(
            "require_once APP_ROOT . '/tests/fixtures/before/common/Model.php';",
            $statement->getFixedString(realpath(__DIR__ . '/../../'), 'APP_ROOT')
        );
        $this->assertEquals('include_path', $statement->type());
    }
}
