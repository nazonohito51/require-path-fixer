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

    public function matchFiles($path)
    {
        // TODO: if $path start with './', must use currentDir.
        // ex: '../../hoge/fuga/../test/./conf/config.php' => 'hoge/fuga/../test/./conf/config.php'
        while (preg_match('|^\.\./|', $path)) {
            $path = substr($path, 3);
        }

        // ex: 'hoge/fuga/../test/./conf/config.php' => 'hoge/test/conf/config.php'
        $path = Path::canonicalize($path);

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
        //TODO: blacklist or empty require statement
        do {
            $this->position++;
        } while ($this->valid() && in_array($this->files[$this->position], $this->blackList));
    }

    public function rewind()
    {
        $this->position = 0;
    }

    public function valid()
    {
        return isset($this->files[$this->position]);
    }
}
