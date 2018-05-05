# RequirePathFixer

Legacy php project have a large number of require statements(require/require_once/include/include_once).
And since they are not homogeneous, it is difficult to collect them all.
This library searches all require statements and modifies them to be homogeneous code.

[![Latest Stable Version](https://poser.pugx.org/nazonohito51/require-path-fixer/version)](https://packagist.org/packages/nazonohito51/require-path-fixer)

```php
// before
require_once 'path/to/file.php';
require_once __DIR__ . '/path/to/file.php';
require_once dirname(__FILE__).'/path/to/file.php';
require_once('../app_root/path/to/file.php');
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
```
composer require "nazonohito51/require-path-fixer"
```

## Usage

```php
require_once __DIR__. '/vendor/autoload.php';
$fixer = new \RequirePathFixer\Fixer($inspectDirPath);   // It is strongly recommended that $inspectDirPath be a repository root.

$fixer->report($inspectDirPath, "APP_ROOT");    // Only reporting.
$fixer->fix($inspectDirPath, "APP_ROOT");       // Fix all files.
// The first argument of these methods is the base path of the modified statement.
// The second argument is a constant or method representing the base path.
// ex: "APP_ROOT", "Config::get('app.root')"
```

## Advanced usage
### Define constants and variables
The following statement will be not replaced, because it is unknown what path COMMON_DIR is.

`require_once COMMON_DIR . '/path/to/file.php';`

If there is a constant or variable to be replaced, it is passed to the following method.
```php
$fixer->addConstant('COMMON_DIR', '/path/to/common/dir/');   // COMMON_DIR will be replaced to '/path/to/common/dir/'
$fixer->addVariable('$smartyDir', '/path/to/smarty/dir/');   // $smartyDir will be replaced to '/path/to/smarty/dir/'
```

### Restrict modification target
If you have files or directories you don't want to modify, use `addBlackList()`.
```php
$fixer->addBlackList($inspectDirPath . '/vendor');
$fixer->addBlackList($inspectDirPath . '/tests');
```

Or if you want to modify only some files in the repository, use `addWhiteList()`. `addBlackList()` and `addWhiteList()` can be used at the same time.

```php
$fixer->addWhiteList($inspectDirPath . '/app');
```

### Define current directory
In PHP, the following statement (starting with '.' or '..') determines the path from the current directory.

`require_once './path/to/file.php';`

Details are written in the [PHP Manual](http://php.net/manual/en/function.include.php).
If you want to define the current directory, use `setWorkingDir()`.
When the current directory is defined, this library resolves the above statement from the current directory.
However, originally the current directory changes depending on the entry point, and it also dynamically changes using `chdir()`.
Please be careful when using it.

```php
$fixer->setWorkingDir($inspectDirPath . '/public');
```

### Define include_path
In PHP, the following statement (relative path) determines the path from the include_path.

`require_once 'path/to/file.php';`

If you want to define the include_path, use `setIncludePath()`.
When include_path is defined, this library resolves the above statement from include_path.
However, originally include_path changes dynamically with `set_include_path()`.
Please also be careful when using it.

```php
$fixer->setIncludePath(".:{$inspectDirPath}/app");
```

## About analysis logic
This library classifies all statements into seven types and converts them.
Their types are `absolute`, `variable`, `relative`, `unique`, `working_dir`, `include_path` and `unexpected`.

### `absolute`
If the path is an absolute path, fix it to the path from the new base path.

```php
// before
require_once __DIR__ . '/hoge/app_root/path/to/file.php';
require_once (dirname(__FILE__).'/../app_root/' .'path/to/file.php');

// after
require_once APP_ROOT . '/path/to/file.php';
require_once APP_ROOT . '/path/to/file.php';
```

### `variable`
If the path can not be resolved, such as unknown variable or constant, do not convert.

```php
// before
require_once UNKNOWN_CONSTANT . 'path/to/file.php';
require_once $unknownVariable . 'path/to/file.php';

// after
require_once UNKNOWN_CONSTANT . 'path/to/file.php';
require_once $unknownVariable . 'path/to/file.php';
```

### `relative`
In the path is an relative path, basically it is determined only at runtime, so it can not be replaced.
But this library somehow tries to guess the path.
That's `unique`, `working_dir`, `include_path`.
However, if neither can be guessed, it will not be converted.
That's `relative`.

```php
// before
require_once 'path/to/file.php';
require_once './path/to/file.php';
require_once '../path/to/file.php';
require_once COMMON_DIR . '/path/to/file.php';   // COMMON_DIR is './app_root/common'

// after
require_once 'path/to/file.php';
require_once './path/to/file.php';
require_once '../path/to/file.php';
require_once COMMON_DIR . '/path/to/file.php';
```

### `unique`
In relative paths, if there is only one file in the repository that matches the path in statement, this library converts it to the path to that file.
```php
// before
require_once 'path/to/file.php';    // There is only one file that matches '/path\/to\/file\.php$/'.
require_once './hoge/fuga.php';    // There is only one file that matches '/hoge\/fuga\.php$/'.
require_once '../foo/bar.php';    // There was no file that matched the '/foo\/bar\.php$/', or there were multiple files.

// after
require_once APP_ROOT . '/path/to/file.php';
require_once APP_ROOT . '/path/to/hoge/fuga.php';
require_once '../foo/bar.php';
```

### `working_dir`
In relative paths starting with '.' or '..', if define current directory by `setWorkingDir()`, this library resolves the path from the current directory.
And this library will not guess by `unique`.

```php
$fixer->setWorkingDir($inspectDirPath . '/public');

// before
require_once './hoge/fuga.php';
require_once '../foo/bar.php';
require_once COMMON_DIR . '/path/to/file.php';   // COMMON_DIR is './app_root/common'

// after
require_once APP_ROOT . '/public/hoge/fuga.php';
require_once APP_ROOT . '/foo/bar.php';
require_once APP_ROOT . '/common/path/to/file.php';
```

### `include_path`
In relative paths not starting with '.' or '..', if define include_path by `setIncludePath()`, this library resolves the path from the include_path.
And this library will not guess by `unique`.

```php
$fixer->setIncludePath('.:' . $inspectDirPath . '/common');   // '.' will be replace current directory, but if it is not defined, '.' will be ignored

// before
require_once 'hoge/fuga.php';
require_once 'foo/bar.php';

// after
require_once APP_ROOT . '/common/hoge/fuga.php';
require_once APP_ROOT . '/common/foo/bar.php';
```

### `unexpected`
The statement will be of this type if it does not fall under either type.
