<?php
namespace RequirePathFixer;

use LucidFrame\Console\ConsoleTable;
use RequirePathFixer\Fixer\PhpFile;
use RequirePathFixer\Fixer\PhpFileCollection;
use RequirePathFixer\Fixer\RequireStatement;
use Symfony\Component\Finder\Finder;
use Webmozart\PathUtil\Path;

class Fixer
{
    private $includePaths = array();
    private $collection;

    public function __construct($dir)
    {
        $this->collection = new PhpFileCollection($dir);
    }

    public function getFiles()
    {
        return $this->collection->getFiles();
    }

    public function fix($requireBase, $constant = null)
    {
        foreach ($this->collection as $phpFile) {
            $statements = $phpFile->getRequireStatements();
            if (empty($statements)) {
                continue;
            }

            foreach ($statements as $statement) {
                if ($statement->type() == 'relative') {
                    $this->guessRequireFile($statement);
                }
            }

            $content = $phpFile->getFixedContents($requireBase, $constant);
            $splFile = new \SplFileObject($phpFile->path(), 'w');
            $splFile->fwrite($content);
        }
    }

    public function report($requireBase, $constant = null)
    {
        $aggregate = array(
            'absolute' => 0,
            'guess' => 0,
            'relative' => 0,
            'variable' => 0,
            'unexpected' => 0,
        );

        foreach ($this->reportByArray($requireBase, $constant) as $file => $statements) {
            $table = new ConsoleTable();
            $table->addHeader('before');
            $table->addHeader('after');
            $table->addHeader('type');

            foreach ($statements as $statement) {
                $table->addRow();
                $table->addColumn($statement['before']);
                $table->addColumn($statement['after']);
                $table->addColumn($statement['type']);

                $aggregate[$statement['type']]++;
            }

            echo $file . "\n";
            $table->display();
            echo "\n\n";
        }

        echo "absolute:{$aggregate['absolute']}, guess:{$aggregate['guess']}, relative:{$aggregate['relative']}, variable:{$aggregate['variable']}, unexpected:{$aggregate['unexpected']}\n";
    }

    public function reportByArray($requireBase, $constant = null)
    {
        $report = array();

        foreach ($this->collection as $phpFile) {
            $statements = $phpFile->getRequireStatements();
            if (empty($statements)) {
                continue;
            }

            $report[$phpFile->path()] = array();
            foreach ($statements as $statement) {
                if ($statement->type() == 'relative') {
                    $this->guessRequireFile($statement);
                }

                $report[$phpFile->path()][] = array(
                    'before' => $statement->string(),
                    'after' => $statement->getFixedString($requireBase, $constant),
                    'type' => $statement->type(),
                );
            }
        }

        return $report;
    }

    private function guessRequireFile(RequireStatement $statement)
    {
        $path = $statement->getRequireFile();

        if ($matchFile = $this->guessRequireFileByIncludePath($path)) {
            $statement->guess($matchFile);
        } elseif ($matchFile = $this->guessRequireFileByAllFiles($path)) {
            $statement->guess($matchFile);
        }
    }

    private function guessRequireFileByIncludePath($path)
    {
        // TODO: if $path start with '.' or '..', don't use include_path. Use currentDir.
        foreach ($this->includePaths as $includePath) {
            $joinedPath = Path::canonicalize(Path::join($includePath, $path));
            if (in_array($joinedPath, $this->getFiles())) {
                return $joinedPath;
            }
        }

        return null;
    }

    private function guessRequireFileByAllFiles($path)
    {
        // ex: '../../hoge/fuga/../test/./conf/config.php' => 'hoge/fuga/../test/./conf/config.php'
        while (preg_match('|^\.\./|', $path)) {
            $path = substr($path, 3);
        }
        // ex: 'hoge/fuga/../test/./conf/config.php' => 'hoge/test/conf/config.php'
        $path = Path::canonicalize($path);

        $pattern = '/' . preg_quote($path, '/') . '$/';
        $matches = array();
        foreach ($this->getFiles() as $file) {
            if (preg_match($pattern, $file)) {
                $matches[] = $file;
            }
        }

        if (count($matches) === 1) {
            return $matches[0];
        }

        return null;
    }

    public function addBlackList($path)
    {
        $this->collection->addBlackList($path);
    }

    public function addVariable($variable, $value)
    {
        $this->collection->addReplacement($variable, $value);
    }

    public function addConstant($constant, $value)
    {
        $this->collection->addReplacement($constant, $value);
    }

    public function addIncludePath($path)
    {
        $this->includePaths[] = $path;
    }

    // TODO: add currentDir and disableGuess
}
