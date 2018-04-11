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

    public function testReportByArray()
    {
        $viewFilePath = realpath(__DIR__ . '/fixtures/before/View.php');
        $modelFilePath = realpath(__DIR__ . '/fixtures/before/common/Model.php');
        $configFilePath = realpath(__DIR__ . '/fixtures/before/conf/config.php');
        $constFilePath = realpath(__DIR__ . '/fixtures/before/conf/const.php');

        $fixer = new Fixer(__DIR__ . '/fixtures/before');
        $report = $fixer->reportByArray(__DIR__ . '/..', 'APP_ROOT');

        $this->assertArrayHasKey($viewFilePath, $report);
        $this->assertContains(array(
            'before' => "require_once dirname(__FILE__) . '/conf/config.php';",
            'after' => "require_once APP_ROOT . '/tests/fixtures/before/conf/config.php';",
            'type' => 'absolute',
        ), $report[$viewFilePath]);
        $this->assertContains(array(
            'before' => "include_once __DIR__ . '/conf/const.php';",
            'after' => "include_once APP_ROOT . '/tests/fixtures/before/conf/const.php';",
            'type' => 'absolute',
        ), $report[$viewFilePath]);
        $this->assertContains(array(
            'before' => "require_once 'common/Model.php';",
            'after' => "require_once APP_ROOT . '/tests/fixtures/before/common/Model.php';",
            'type' => 'unique',
        ), $report[$viewFilePath]);
        $this->assertContains(array(
            'before' => "require_once './common/Model.php';",
            'after' => "require_once APP_ROOT . '/tests/fixtures/before/common/Model.php';",
            'type' => 'unique',
        ), $report[$viewFilePath]);
        $this->assertContains(array(
            'before' => "require_once \$type . 'Model.php';",
            'after' => null,
            'type' => 'variable',
        ), $report[$viewFilePath]);
        $this->assertContains(array(
            'before' => "require_once COMMON_DIR . 'Model.php';",
            'after' => null,
            'type' => 'variable',
        ), $report[$viewFilePath]);

        $this->assertArrayHasKey($modelFilePath, $report);
        $this->assertContains(array(
            'before' => "require __DIR__ . '/../conf/config.php';",
            'after' => "require APP_ROOT . '/tests/fixtures/before/conf/config.php';",
            'type' => 'absolute',
        ), $report[$modelFilePath]);
        $this->assertContains(array(
            'before' => "include __DIR__ . '/../conf/const.php';",
            'after' => "include APP_ROOT . '/tests/fixtures/before/conf/const.php';",
            'type' => 'absolute',
        ), $report[$modelFilePath]);

        $this->assertArrayHasKey($configFilePath, $report);
        $this->assertContains(array(
            'before' => "include_once dirname(__FILE__) . '/const.php';",
            'after' => "include_once APP_ROOT . '/tests/fixtures/before/conf/const.php';",
            'type' => 'absolute',
        ), $report[$configFilePath]);
    }

    public function testReportByArray_SetIncludePath()
    {
        $viewFilePath = realpath(__DIR__ . '/fixtures/before/View.php');
        $modelFilePath = realpath(__DIR__ . '/fixtures/before/common/Model.php');
        $configFilePath = realpath(__DIR__ . '/fixtures/before/conf/config.php');
        $constFilePath = realpath(__DIR__ . '/fixtures/before/conf/const.php');

        $fixer = new Fixer(__DIR__ . '/fixtures/before');
        $fixer->setIncludePath('.:' . __DIR__ . '/fixtures/before');
        $report = $fixer->reportByArray(__DIR__ . '/..', 'APP_ROOT');

        $this->assertArrayHasKey($viewFilePath, $report);
        $this->assertContains(array(
            'before' => "require_once dirname(__FILE__) . '/conf/config.php';",
            'after' => "require_once APP_ROOT . '/tests/fixtures/before/conf/config.php';",
            'type' => 'absolute',
        ), $report[$viewFilePath]);
        $this->assertContains(array(
            'before' => "include_once __DIR__ . '/conf/const.php';",
            'after' => "include_once APP_ROOT . '/tests/fixtures/before/conf/const.php';",
            'type' => 'absolute',
        ), $report[$viewFilePath]);
        $this->assertContains(array(
            'before' => "require_once 'common/Model.php';",
            'after' => "require_once APP_ROOT . '/tests/fixtures/before/common/Model.php';",
            'type' => 'include_path',
        ), $report[$viewFilePath]);
        $this->assertContains(array(
            'before' => "require_once './common/Model.php';",
            'after' => "require_once APP_ROOT . '/tests/fixtures/before/common/Model.php';",
            'type' => 'unique',
        ), $report[$viewFilePath]);
        $this->assertContains(array(
            'before' => "require_once \$type . 'Model.php';",
            'after' => null,
            'type' => 'variable',
        ), $report[$viewFilePath]);
        $this->assertContains(array(
            'before' => "require_once COMMON_DIR . 'Model.php';",
            'after' => null,
            'type' => 'variable',
        ), $report[$viewFilePath]);

        $this->assertArrayHasKey($modelFilePath, $report);
        $this->assertContains(array(
            'before' => "require __DIR__ . '/../conf/config.php';",
            'after' => "require APP_ROOT . '/tests/fixtures/before/conf/config.php';",
            'type' => 'absolute',
        ), $report[$modelFilePath]);
        $this->assertContains(array(
            'before' => "include __DIR__ . '/../conf/const.php';",
            'after' => "include APP_ROOT . '/tests/fixtures/before/conf/const.php';",
            'type' => 'absolute',
        ), $report[$modelFilePath]);

        $this->assertArrayHasKey($configFilePath, $report);
        $this->assertContains(array(
            'before' => "include_once dirname(__FILE__) . '/const.php';",
            'after' => "include_once APP_ROOT . '/tests/fixtures/before/conf/const.php';",
            'type' => 'absolute',
        ), $report[$configFilePath]);
    }

    public function testReportByArray_SetWorkingDir()
    {
        $viewFilePath = realpath(__DIR__ . '/fixtures/before/View.php');
        $modelFilePath = realpath(__DIR__ . '/fixtures/before/common/Model.php');
        $configFilePath = realpath(__DIR__ . '/fixtures/before/conf/config.php');
        $constFilePath = realpath(__DIR__ . '/fixtures/before/conf/const.php');

        $fixer = new Fixer(__DIR__ . '/fixtures/before');
        $fixer->setWorkingDir(__DIR__ . '/fixtures/before');
        $report = $fixer->reportByArray(__DIR__ . '/..', 'APP_ROOT');

        $this->assertArrayHasKey($viewFilePath, $report);
        $this->assertContains(array(
            'before' => "require_once dirname(__FILE__) . '/conf/config.php';",
            'after' => "require_once APP_ROOT . '/tests/fixtures/before/conf/config.php';",
            'type' => 'absolute',
        ), $report[$viewFilePath]);
        $this->assertContains(array(
            'before' => "include_once __DIR__ . '/conf/const.php';",
            'after' => "include_once APP_ROOT . '/tests/fixtures/before/conf/const.php';",
            'type' => 'absolute',
        ), $report[$viewFilePath]);
        $this->assertContains(array(
            'before' => "require_once 'common/Model.php';",
            'after' => "require_once APP_ROOT . '/tests/fixtures/before/common/Model.php';",
            'type' => 'unique',
        ), $report[$viewFilePath]);
        $this->assertContains(array(
            'before' => "require_once './common/Model.php';",
            'after' => "require_once APP_ROOT . '/tests/fixtures/before/common/Model.php';",
            'type' => 'working_dir',
        ), $report[$viewFilePath]);
        $this->assertContains(array(
            'before' => "require_once \$type . 'Model.php';",
            'after' => null,
            'type' => 'variable',
        ), $report[$viewFilePath]);
        $this->assertContains(array(
            'before' => "require_once COMMON_DIR . 'Model.php';",
            'after' => null,
            'type' => 'variable',
        ), $report[$viewFilePath]);

        $this->assertArrayHasKey($modelFilePath, $report);
        $this->assertContains(array(
            'before' => "require __DIR__ . '/../conf/config.php';",
            'after' => "require APP_ROOT . '/tests/fixtures/before/conf/config.php';",
            'type' => 'absolute',
        ), $report[$modelFilePath]);
        $this->assertContains(array(
            'before' => "include __DIR__ . '/../conf/const.php';",
            'after' => "include APP_ROOT . '/tests/fixtures/before/conf/const.php';",
            'type' => 'absolute',
        ), $report[$modelFilePath]);

        $this->assertArrayHasKey($configFilePath, $report);
        $this->assertContains(array(
            'before' => "include_once dirname(__FILE__) . '/const.php';",
            'after' => "include_once APP_ROOT . '/tests/fixtures/before/conf/const.php';",
            'type' => 'absolute',
        ), $report[$configFilePath]);
    }

    public function testReportByArray_DisableGuess()
    {
        $viewFilePath = realpath(__DIR__ . '/fixtures/before/View.php');
        $modelFilePath = realpath(__DIR__ . '/fixtures/before/common/Model.php');
        $configFilePath = realpath(__DIR__ . '/fixtures/before/conf/config.php');
        $constFilePath = realpath(__DIR__ . '/fixtures/before/conf/const.php');

        $fixer = new Fixer(__DIR__ . '/fixtures/before');
        $fixer->disableGuess();
        $report = $fixer->reportByArray(__DIR__ . '/..', 'APP_ROOT');

        $this->assertArrayHasKey($viewFilePath, $report);
        $this->assertContains(array(
            'before' => "require_once dirname(__FILE__) . '/conf/config.php';",
            'after' => "require_once APP_ROOT . '/tests/fixtures/before/conf/config.php';",
            'type' => 'absolute',
        ), $report[$viewFilePath]);
        $this->assertContains(array(
            'before' => "include_once __DIR__ . '/conf/const.php';",
            'after' => "include_once APP_ROOT . '/tests/fixtures/before/conf/const.php';",
            'type' => 'absolute',
        ), $report[$viewFilePath]);
        $this->assertContains(array(
            'before' => "require_once 'common/Model.php';",
            'after' => null,
            'type' => 'relative',
        ), $report[$viewFilePath]);
        $this->assertContains(array(
            'before' => "require_once './common/Model.php';",
            'after' => null,
            'type' => 'relative',
        ), $report[$viewFilePath]);
        $this->assertContains(array(
            'before' => "require_once \$type . 'Model.php';",
            'after' => null,
            'type' => 'variable',
        ), $report[$viewFilePath]);
        $this->assertContains(array(
            'before' => "require_once COMMON_DIR . 'Model.php';",
            'after' => null,
            'type' => 'variable',
        ), $report[$viewFilePath]);

        $this->assertArrayHasKey($modelFilePath, $report);
        $this->assertContains(array(
            'before' => "require __DIR__ . '/../conf/config.php';",
            'after' => "require APP_ROOT . '/tests/fixtures/before/conf/config.php';",
            'type' => 'absolute',
        ), $report[$modelFilePath]);
        $this->assertContains(array(
            'before' => "include __DIR__ . '/../conf/const.php';",
            'after' => "include APP_ROOT . '/tests/fixtures/before/conf/const.php';",
            'type' => 'absolute',
        ), $report[$modelFilePath]);

        $this->assertArrayHasKey($configFilePath, $report);
        $this->assertContains(array(
            'before' => "include_once dirname(__FILE__) . '/const.php';",
            'after' => "include_once APP_ROOT . '/tests/fixtures/before/conf/const.php';",
            'type' => 'absolute',
        ), $report[$configFilePath]);
    }
}
