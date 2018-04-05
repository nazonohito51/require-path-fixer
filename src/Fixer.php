<?php
namespace RequirePathFixer;

use LucidFrame\Console\ConsoleTable;
use RequirePathFixer\Fixer\File;
use Symfony\Component\Finder\Finder;

class Fixer
{
    private $path;
    private $files = array();

    public function __construct($dir)
    {
        $this->path = $dir;
        $finder = new Finder();
        $iterator = $finder->in($dir)->name('*.php')->files();
        foreach ($iterator as $fileInfo) {
            $this->files[] = $fileInfo->getPathname();
        }
    }

    public function getFiles()
    {
        return $this->files;
    }

    public function report($requireBase, $constant = null)
    {
        foreach ($this->files as $file) {
            $phpFile = new File($file);
            $statements = $phpFile->getRequireStatements();
            if (empty($statements)) {
                continue;
            }

            $table = new ConsoleTable();
            $table->addHeader('before');
            $table->addHeader('after');
            $table->addHeader('type');
            foreach ($statements as $statement) {
                $table->addRow();
                $table->addColumn($statement->string());
                $table->addColumn($statement->getFixedStatement($requireBase, $constant));
                $table->addColumn($statement->type());
            }

            echo $file . "\n";
            $table->display();
            echo "\n\n";
        }
    }
}
