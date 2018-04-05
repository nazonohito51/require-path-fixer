<?php
namespace RequirePathFixer;

use LucidFrame\Console\ConsoleTable;
use RequirePathFixer\Fixer\File;
use RequirePathFixer\Fixer\RequireStatement;
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
                if ($statement->type() == 'relative') {
                    $this->guessRequiredFile($statement);
                }

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

    private function guessRequiredFile(RequireStatement $statement)
    {
        $pattern = '/' . preg_quote($statement->getRequiredFilePath(), '/') . '$/';
        $matches = array();
        foreach ($this->files as $file) {
            if (preg_match($pattern, $file)) {
                $matches[] = $file;
            }
        }

        if (count($matches) === 1) {
            $statement->guess($matches[0]);
        }
    }
}
