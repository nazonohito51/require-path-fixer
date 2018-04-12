# RequirePathFixer

Legacy php project have a large number of require statements(require/require_once/include/include_once).
And since they are not homogeneous, it is difficult to collect them all.
This library searches all require statements and modifies them to be homogeneous code.

[![Latest Stable Version](https://poser.pugx.org/nazonohito51/require-path-fixer/version)](https://packagist.org/packages/nazonohito51/require-path-fixer)

```php
// before
require_once 'path/to/file.php';
require_once __DIR__ . 'path/to/file.php';
require_once dirname(__FILE__).'path/to/file.php';
require_once('../repository_root/path/to/file.php');
require_once      (   'path/' .
     'to/'                                  .         'file.php'     );
require 'path/to/file.php';
include_once 'path/to/file.php';
include 'path/to/file.php';
require_once UNKNOWN_CONSTANT . 'path/to/file.php';

// after
require_once APP_ROOT . '/path/to/file.php';
require_once APP_ROOT . '/path/to/file.php';
require_once APP_ROOT . '/path/to/file.php';
require_once APP_ROOT . '/path/to/file.php';
require_once APP_ROOT . '/path/to/file.php';
require APP_ROOT . '/path/to/file.php';
include_once APP_ROOT . '/path/to/file.php';
include APP_ROOT . '/path/to/file.php';
require_once UNKNOWN_CONSTANT . 'path/to/file.php';
```

## Installation

## Usage

```php
require_once __DIR__. '/vendor/autoload.php';
$fixer = new \RequirePathFixer\Fixer($inspectDirPath);   // It is strongly recommended that $inspectDirPath be a repository root.

// The following statement will be not replaced, because it is unknown what path COMMON_DIR is.
// require_once COMMON_DIR . '/path/to/file.php';
// If there is a constant or variable to be replaced, it is passed to the following method.
$fixer->addConstant('COMMON_DIR', '/path/to/common/dir/');   // COMMON_DIR will be replaced to '/path/to/common/dir/'
$fixer->addVariable('$smartyDir', '/path/to/smarty/dir/');   // $smartyDir will be replaced to '/path/to/smarty/dir/'

$fixer->report($inspectDirPath, "APP_ROOT");    // Only reporting.
$fixer->fix($inspectDirPath, "APP_ROOT");       // Fix all files.
// The first argument of these methods (report() and fix()) is the base path of the modified statement.
// The second argument is a constant or method representing the base path.
// ex: "APP_ROOT", "Config::get('app.root')"
```

## About analysis logic


## Advanced usage

```php
// If you have files or directories you don't want to modify, pass the path to this method.
$fixer->addBlackList($inspectDirPath . '/vendor');
$fixer->addBlackList($inspectDirPath . '/tests');

// Or if you want to modify only some files in the repository, pass the path to this method.
$fixer->addWhiteList($inspectDirPath . '/app');

// addBlackList() and addWhiteList() can be used at the same time.

// The following statement (starting with . or ..) determines the path from the current directory.
// require_once './path/to/file.php';
// Details are written in the [PHP Manual](http://php.net/manual/en/function.include.php).
// If you want to define the current directory, use setWorkingDir().
// When the current directory is defined, this library resolves the above statement from the current directory.
$fixer->setWorkingDir($inspectDirPath . '/public');

// The following statement (relative path) determines the path from the include_path.
// require_once 'path/to/file.php';
// If you want to define the include_path, use setIncludePath().
// When include_path is defined, this library resolves the above statement from include_path.
$fixer->setIncludePath('.:' . $inspectDirPath . '/app');
```
