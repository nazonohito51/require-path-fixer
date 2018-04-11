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
    private $collection;

    private $includePaths = array();
    private $workingDir;

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
                if (!$statement->isFixable() && $statement->isRelative()) {
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
            'unique' => 0,
            'working_dir' => 0,
            'include_path' => 0,
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
                if (!$statement->isFixable() && $statement->isRelative()) {
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
        if (Path::isAbsolute($path = $statement->getRequireFile())) {
            throw new \LogicException("{$path} can not be guessed because \$path is a absolute path.");
        } elseif ($statement->isIncludeStatement()) {
            // If $statement is 'include' or 'include_once', there is a possibility that this statement is currently failing to read.
            // In that case, since reading will be successful by modifying it, do not do modify.
            return;
        }

        if (substr($path, 0, 1) === '.' && !is_null($this->workingDir)) {
            $statement->guessFromWorkingDir(Path::join($this->workingDir, $path));
        } elseif (substr($path, 0, 1) !== '.' && !empty($this->includePaths)) {
            foreach ($this->includePaths as $includePath) {
                $files = $this->collection->matches(Path::join($includePath, $path));
                if (count($files) === 1) {
                    $statement->guessFromIncludePath($files[0]);
                    break;
                }
            }
        } else {
            $files = $this->collection->matches($path);
            if (count($files) === 1) {
                $statement->guessFromUnique($files[0]);
            }
        }
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

    public function setIncludePath($includePath)
    {
        $paths = explode(':', $includePath);

        $this->includePaths = array();
        foreach ($paths as $path) {
            if (is_dir($dir = realpath($path))) {
                $this->includePaths[] = $dir;
            } else {
                throw new \InvalidArgumentException("{$path} is not a directory.");
            }
        }
    }

    public function setWorkingDir($workingDir)
    {
        if (is_dir($dir = realpath($workingDir))) {
            $this->workingDir = $dir;
        } else {
            throw new \InvalidArgumentException("{$workingDir} is not a directory.");
        }
    }

    // TODO: add currentDir and disableGuess
}
