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

    private $guessable = true;
    private $guessOnInclude = false;

    public function __construct($dir)
    {
        $this->collection = new PhpFileCollection($dir);

        if (!ini_get('short_open_tag')) {
            echo "WARNING!\n";
            echo "'short_open_tag' in php.ini is disabled. If there is a php file using short_open_tag, that will not be replaced.\n";
            echo "\n\n";
        }
    }

    public function getFiles()
    {
        return $this->collection->getFiles();
    }

    public function fix($requireBase, $constant = null)
    {
        $this->run(function (PhpFile $phpFile) use ($requireBase, $constant) {
            $content = $phpFile->getFixedContents($requireBase, $constant);
            $splFile = new \SplFileObject($phpFile->path(), 'w');
            $splFile->fwrite($content);
        });
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

        $this->run(function (PhpFile $phpFile) use (&$aggregate, $requireBase, $constant) {
            $table = new ConsoleTable();
            $table->addHeader('before');
            $table->addHeader('after');
            $table->addHeader('type');

            foreach ($phpFile->getRequireStatements() as $statement) {
                $table->addRow();
                $table->addColumn($statement->string());
                $table->addColumn($statement->getFixedString($requireBase, $constant));
                $table->addColumn($statement->type());

                $aggregate[$statement->type()]++;
            }

            echo $phpFile->path() . "\n";
            $table->display();
            echo "\n\n";
        });

        echo "absolute:{$aggregate['absolute']}, unique:{$aggregate['unique']}, working_dir:{$aggregate['working_dir']}, include_path:{$aggregate['include_path']}, relative:{$aggregate['relative']}, variable:{$aggregate['variable']}, unexpected:{$aggregate['unexpected']}\n";
    }

    public function reportByArray($requireBase, $constant = null)
    {
        $report = array();
        $this->run(function (PhpFile $phpFile) use (&$report, $requireBase, $constant) {
            $report[$phpFile->path()] = array();
            foreach ($phpFile->getRequireStatements() as $statement) {
                $report[$phpFile->path()][] = array(
                    'before' => $statement->string(),
                    'after' => $statement->getFixedString($requireBase, $constant),
                    'type' => $statement->type(),
                );
            }
        });

        return $report;
    }

    private function run(callable $procedure = null)
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

            if ($procedure) {
                $procedure($phpFile);
            }
        }
    }

    private function guessRequireFile(RequireStatement $statement)
    {
        if (Path::isAbsolute($path = $statement->getRequireFile())) {
            throw new \LogicException("{$path} can not be guessed because \$path is a absolute path.");
        } elseif (!$this->guessable) {
            return;
        } elseif ($statement->isIncludeStatement() && !$this->guessOnInclude) {
            // If $statement is 'include' or 'include_once', there is a possibility that this statement is currently failing to read.
            // In that case, since reading will be successful by modifying it, do not do modify.
            return;
        }

        if (substr($path, 0, 1) === '.' && !is_null($this->workingDir)) {
            $statement->guessFromWorkingDir(Path::join($this->workingDir, $path));
        } elseif (substr($path, 0, 1) !== '.' && !empty($this->includePaths)) {
            foreach ($this->includePaths as $includePath) {
                if ($includePath === '.') {
                    if (empty($this->workingDir)) {
                        continue;
                    }
                    $includePath = $this->workingDir;
                }
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

    public function addWhiteList($path)
    {
        $this->collection->addWhiteList($path);
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
            $dir = ($path !== '.') ? realpath($path) : $path;
            if (is_dir($dir) || $dir === '.') {
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

    public function disableGuess()
    {
        $this->guessable = false;
    }

    public function enableGuessOnIncludeStatement()
    {
        $this->guessOnInclude = true;
    }
}
