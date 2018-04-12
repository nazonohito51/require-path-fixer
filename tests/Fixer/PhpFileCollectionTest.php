<?php
namespace RequirePathFixer\Fixer;

use PHPUnit\Framework\TestCase;

class PhpFileCollectionTest extends TestCase
{
    public function testGetFiles()
    {
        $collection = new PhpFileCollection(__DIR__ . '/../fixtures/before');

        $files = $collection->getFiles();

        $this->assertCount(4, $files);
        $this->assertContains(realpath(__DIR__ . '/../fixtures/before/View.php'), $files);
        $this->assertContains(realpath(__DIR__ . '/../fixtures/before/common/Model.php'), $files);
        $this->assertContains(realpath(__DIR__ . '/../fixtures/before/conf/config.php'), $files);
        $this->assertContains(realpath(__DIR__ . '/../fixtures/before/conf/const.php'), $files);
    }

    public function testIterativeAccess()
    {
        $collection = new PhpFileCollection(__DIR__ . '/../fixtures/before');

        $paths = array();
        foreach ($collection as $file) {
            $this->assertInstanceOf(__NAMESPACE__ . '\PhpFile', $file);
            $paths[] = $file->path();
        }

        $this->assertCount(4, $paths);
        $this->assertContains(realpath(__DIR__ . '/../fixtures/before/View.php'), $paths);
        $this->assertContains(realpath(__DIR__ . '/../fixtures/before/common/Model.php'), $paths);
        $this->assertContains(realpath(__DIR__ . '/../fixtures/before/conf/config.php'), $paths);
        $this->assertContains(realpath(__DIR__ . '/../fixtures/before/conf/const.php'), $paths);
    }

    public function testAddBlackList()
    {
        $collection = new PhpFileCollection(__DIR__ . '/../fixtures/before');
        $collection->addBlackList(__DIR__ . '/../fixtures/before/conf');

        $paths = array();
        foreach ($collection as $file) {
            $this->assertInstanceOf(__NAMESPACE__ . '\PhpFile', $file);
            $paths[] = $file->path();
        }

        $this->assertCount(2, $paths);
        $this->assertContains(realpath(__DIR__ . '/../fixtures/before/View.php'), $paths);
        $this->assertContains(realpath(__DIR__ . '/../fixtures/before/common/Model.php'), $paths);
        $this->assertNotContains(realpath(__DIR__ . '/../fixtures/before/conf/config.php'), $paths);
        $this->assertNotContains(realpath(__DIR__ . '/../fixtures/before/conf/const.php'), $paths);
    }

    public function testAddWhiteList()
    {
        $collection = new PhpFileCollection(__DIR__ . '/../fixtures/before');
        $collection->addWhiteList(__DIR__ . '/../fixtures/before/conf');

        $paths = array();
        foreach ($collection as $file) {
            $this->assertInstanceOf(__NAMESPACE__ . '\PhpFile', $file);
            $paths[] = $file->path();
        }

        $this->assertCount(2, $paths);
        $this->assertNotContains(realpath(__DIR__ . '/../fixtures/before/View.php'), $paths);
        $this->assertNotContains(realpath(__DIR__ . '/../fixtures/before/common/Model.php'), $paths);
        $this->assertContains(realpath(__DIR__ . '/../fixtures/before/conf/config.php'), $paths);
        $this->assertContains(realpath(__DIR__ . '/../fixtures/before/conf/const.php'), $paths);
    }

    public function testMatches()
    {
        $modelFilePath = realpath(__DIR__ . '/../fixtures/before/common/Model.php');
        $collection = new PhpFileCollection(__DIR__ . '/../fixtures/before');

        $this->assertCount(1, $collection->matches(__DIR__ . '/../fixtures/before/common/Model.php'));
        $this->assertContains($modelFilePath, $collection->matches(__DIR__ . '/../fixtures/before/common/Model.php'));
        $this->assertCount(1, $collection->matches('common/Model.php'));
        $this->assertContains($modelFilePath, $collection->matches('common/Model.php'));
        $this->assertCount(1, $collection->matches('Model.php'));
        $this->assertContains($modelFilePath, $collection->matches('Model.php'));
        $this->assertCount(0, $collection->matches('conf/Model.php'));
    }
}
