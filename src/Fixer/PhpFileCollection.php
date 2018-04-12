<?php
namespace RequirePathFixer\Fixer;

use Symfony\Component\Finder\Finder;
use Webmozart\PathUtil\Path;

class PhpFileCollection implements \Iterator
{
    private $rootDir;
    private $position = 0;
    private $files = array();
    private $replacements = array();
    private $blackList = array();
    private $whiteList = array();

    public function __construct($dir)
    {
        $dir = realpath($dir);
        if (!is_dir($dir)) {
            throw new \InvalidArgumentException("{$dir} is not a directory.");
        }

        $this->rootDir = $dir;
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

    public function addReplacement($key, $value)
    {
        $this->replacements[$key] = $value;
    }

    public function addBlackList($path)
    {
        if (is_file($path)) {
            $this->blackList[] = Path::canonicalize($path);
        } elseif (is_dir($path)) {
            $finder = new Finder();
            $iterator = $finder->in($path)->name('*.php')->name('*.inc')->files();
            foreach ($iterator as $fileInfo) {
                $this->blackList[] = Path::canonicalize($fileInfo->getPathname());
            }
        } else {
            throw new \InvalidArgumentException("{$path} is not a file and directory.");
        }
    }

    public function addWhiteList($path)
    {
        if (is_file($path)) {
            $this->whiteList[] = Path::canonicalize($path);
        } elseif (is_dir($path)) {
            $finder = new Finder();
            $iterator = $finder->in($path)->name('*.php')->name('*.inc')->files();
            foreach ($iterator as $fileInfo) {
                $this->whiteList[] = Path::canonicalize($fileInfo->getPathname());
            }
        } else {
            throw new \InvalidArgumentException("{$path} is not a file and directory.");
        }
    }

    public function matches($path)
    {
        // ex: './../hoge/fuga/../test/./conf/config.php' => 'hoge/fuga/../test/./conf/config.php'
        while (preg_match('|^\.\.*/|', $path)) {
            $path = preg_replace('|^\.\.*/|', '', $path);
        }

        // ex: 'hoge/fuga/../test/./conf/config.php' => 'hoge/test/conf/config.php'
        $path = Path::canonicalize($path);

        if (Path::isRelative($path) && !strpos($path, DIRECTORY_SEPARATOR)) {
            // ex: 'config.php' -> '/config.php'
            $path = '/' . $path;
        }

        // ex: 'hoge/test/conf/config.php' => '/hoge\/test\/conf\/config\.php$/'
        $pattern = '/' . preg_quote($path, '/') . '$/';
        $matches = array();
        foreach ($this->files as $file) {
            if (preg_match($pattern, $file)) {
                $matches[] = $file;
            }
        }

        return $matches;
    }

    public function isInBlackList($file)
    {
        return in_array($file, $this->blackList);
    }

    public function isInWhiteList($file)
    {
        return empty($this->whiteList) || in_array($file, $this->whiteList);
    }

    public function current()
    {
        return new PhpFile($this->files[$this->position], $this->replacements);
    }

    public function key()
    {
        return $this->position;
    }

    public function next()
    {
        do {
            $this->position++;
        } while ($this->valid() && (
            $this->isInBlackList($this->files[$this->position]) ||
            !$this->isInWhiteList($this->files[$this->position])
        ));
    }

    public function rewind()
    {
        $this->position = 0;

        while ($this->valid() && (
            $this->isInBlackList($this->files[$this->position]) ||
            !$this->isInWhiteList($this->files[$this->position])
        )) {
            $this->position++;
        }
    }

    public function valid()
    {
        return isset($this->files[$this->position]);
    }
}
