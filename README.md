# RequirePathFixer

Legacy php project have a large number of require statements(require/require_once/include/include_once).
And since they are not homogeneous, it is difficult to collect them all.
This library searches all require statements and modifies them to be homogeneous code.

## Usage

```
$fixer = new \RequirePathFixer\Fixer(__DIR__ . '/app');
$fixer->addBlackList(__DIR__ . '/vendor');
$fixer->addBlackList(__DIR__ . '/tests');

// Only reporting.
$fixer->report(__DIR__, 'APP_ROOT');

// Fix all files.
$fixer->fix(__DIR__, 'APP_ROOT');
```
