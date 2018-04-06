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
    private $blackList = array();

    public function __construct($dir)
    {
        $dir = realpath($dir);
        if (!is_dir($dir)) {
            throw new \InvalidArgumentException("{$dir} is not directory.");
        }

        $this->path = $dir;
        $finder = new Finder();
        $iterator = $finder->in($dir)->name('*.php')->name('*.inc')->files();
        foreach ($iterator as $fileInfo) {
            $this->files[] = $fileInfo->getPathname();
        }
    }

    public function getFiles()
    {
        return $this->files;
    }

    public function fix($requireBase, $constant = null)
    {
        foreach ($this->files as $file) {
            if (in_array($file, $this->blackList)) {
                continue;
            }

            $phpFile = new File($file);
            $statements = $phpFile->getRequireStatements();
            if (empty($statements)) {
                continue;
            }

            foreach ($statements as $statement) {
                if ($statement->type() == 'relative') {
                    $this->guessRequiredFile($statement);
                }
            }

            $content = $phpFile->fixedRequireStatements($requireBase, $constant);
            $splFile = new \SplFileObject($file, 'w');
            $splFile->fwrite($content);
        }
    }

    public function report($requireBase, $constant = null)
    {
        $result = array(
            'absolute' => 0,
            'guess' => 0,
            'relative' => 0,
            'variable' => 0,
            'unexpected' => 0,
        );

        foreach ($this->files as $file) {
            if (in_array($file, $this->blackList)) {
                continue;
            }

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

                $result[$statement->type()]++;
            }

            echo $file . "\n";
            $table->display();
            echo "\n\n";
        }

        echo "absolute:{$result['absolute']}, guess:{$result['guess']}, relative:{$result['relative']}, variable:{$result['variable']}, unexpected:{$result['unexpected']}\n";
    }

    private function guessRequiredFile(RequireStatement $statement)
    {
        $path = $statement->getRequiredFilePath();
        $path = substr($path, 0 , 1) == DIRECTORY_SEPARATOR ? $path : DIRECTORY_SEPARATOR . $path;
        $pattern = '/' . preg_quote($path, '/') . '$/';
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

    public function addBlackList($path)
    {
        $finder = new Finder();
        $iterator = $finder->in($path)->name('*.php')->files();
        foreach ($iterator as $fileInfo) {
            $this->blackList[] = $fileInfo->getPathname();
        }
    }
}
