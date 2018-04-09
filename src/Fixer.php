<?php
namespace RequirePathFixer;

use LucidFrame\Console\ConsoleTable;
use RequirePathFixer\Fixer\PhpFile;
use RequirePathFixer\Fixer\RequireStatement;
use Symfony\Component\Finder\Finder;
use Webmozart\PathUtil\Path;

class Fixer
{
    private $path;
    private $files = array();
    private $blackList = array();
    private $replacements = array();
    private $includePaths = array();

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

            $phpFile = new PhpFile($file, $this->replacements);
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

            $phpFile = new PhpFile($file, $this->replacements);
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
                    $this->guessRequireFile($statement);
                }

                $table->addRow();
                $table->addColumn($statement->string());
                $table->addColumn($statement->getFixedString($requireBase, $constant));
                $table->addColumn($statement->type());

                $result[$statement->type()]++;
            }

            echo $file . "\n";
            $table->display();
            echo "\n\n";
        }

        echo "absolute:{$result['absolute']}, guess:{$result['guess']}, relative:{$result['relative']}, variable:{$result['variable']}, unexpected:{$result['unexpected']}\n";
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
        // TODO: if $path start with '.' or '..', don't use include_path.
        foreach ($this->includePaths as $includePath) {
            $joinedPath = Path::canonicalize(Path::join($includePath, $path));
            if (in_array($joinedPath, $this->files)) {
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
        foreach ($this->files as $file) {
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
        $finder = new Finder();
        $iterator = $finder->in($path)->name('*.php')->name('*.inc')->files();
        foreach ($iterator as $fileInfo) {
            $this->blackList[] = Path::canonicalize($fileInfo->getPathname());
        }
    }

    public function addVariable($variable, $value)
    {
        $this->replacements[$variable] = $value;
    }

    public function addConstant($constant, $value)
    {
        $this->replacements[$constant] = $value;
    }

    public function addIncludePath($path)
    {
        $this->includePaths[] = $path;
    }
}
