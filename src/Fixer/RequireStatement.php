<?php
namespace RequirePathFixer\Fixer;

use RequirePathFixer\Exceptions\EvalException;
use RequirePathFixer\Exceptions\GenerateEvalCodeException;
use Webmozart\PathUtil\Path;

class RequireStatement
{
    private $file;
    private $requireFile;
    private $tokens;
    private $type;
    private $replacements = array();
    private $evalCode;

//    const TYPE = array('absolute', 'unique', 'working_dir', 'include_path', 'relative', 'variable', 'unexpected');

    public function __construct($file, array $tokens, array $replacement = array())
    {
        $this->file = realpath($file);
        $this->tokens = $tokens;
        $this->replacements = $replacement;

        $this->resolveRequireFile();
    }

    private function resolveRequireFile()
    {
        try {
            // ex:
            //   $this->string() == "require_once dirname(__FILE__) . $variable . '/dir/file.php'";
            //   $code == "return dirname('/path/to/this/php/file.php') . 'replacementText' . '/dir/file.php'";
            $code = 'return ';
            for ($i = 0; $i < count($this->tokens); $i++) {
                $token = $this->tokens[$i];

                if (isset($token[0]) && in_array($token[0], array(T_REQUIRE, T_REQUIRE_ONCE, T_INCLUDE, T_INCLUDE_ONCE))) {
                    continue;
                } elseif (isset($token[0]) && $token[0] == T_FILE) {
                    // __FILE__ -> 'path/to/this/file/file.php'
                    $code .= "'" . $this->file . "'";
                } elseif (isset($token[0]) && $token[0] == T_DIR) {
                    // __DIR__ -> 'path/to/this/file/'
                    $code .= "'" . $this->dir() . "'";
                } elseif (isset($token[0]) && in_array($token[0], array(T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES))) {
                    // {$variable} or ${variable} -> 'replacementText'
                    $complexVariableParsedSyntaxTokens = array();
                    for (; $i < count($this->tokens); $i++) {
                        $complexVariableParsedSyntaxTokens[] = $this->tokens[$i];
                        if ($this->tokens[$i] == '}') {
                            break;
                        }
                    }
                    $code .= $this->replaceComplexVariableParsedSyntax($complexVariableParsedSyntaxTokens);
                } elseif (isset($token[0]) && $token[0] == T_STRING && $token[1] != 'dirname' && $token[1] != 'realpath') {
                    // CONSTANT -> 'replacementText'
                    $code .= $this->replaceToken($token);
                } elseif (isset($token[0]) && $token[0] == T_VARIABLE) {
                    // $variable -> 'replacementText'
                    $code .= $this->replaceToken($token);
                } elseif (is_array($token)) {
                    $code .= $token[1];
                } else {
                    $code .= $token;
                }
            }

            $this->requireFile = $this->execStringConcatenation($code);
            if (Path::isAbsolute($this->requireFile)) {
                $this->type = 'absolute';
            } else {
                $this->type = 'relative';
            }
        } catch (GenerateEvalCodeException $e) {
            $this->requireFile = null;
            $this->type = 'variable';
        } catch (EvalException $e) {
            $this->requireFile = null;
            $this->type = 'unexpected';
        }
    }

    private function replaceComplexVariableParsedSyntax(array $tokens)
    {
        if (count($tokens) == 3 && $tokens[0][0] == T_CURLY_OPEN && $tokens[1][0] == T_VARIABLE && $tokens[2] == '}') {
            if (isset($this->replacements[$tokens[1][1]])) {
                return $this->replacements[$tokens[1][1]];
            }
        } elseif (count($tokens) == 3 && $tokens[0][0] == T_DOLLAR_OPEN_CURLY_BRACES && $tokens[1][0] == T_STRING_VARNAME && $tokens[2] == '}') {
            if (isset($this->replacements['$' . $tokens[1][1]])) {
                return $this->replacements['$' . $tokens[1][1]];
            }
        }

        throw new GenerateEvalCodeException($this->file, $tokens);
    }

    private function replaceToken(array $token)
    {
        if (isset($this->replacements[$token[1]])) {
            return "'" . $this->replacements[$token[1]] . "'";
        }

        throw new GenerateEvalCodeException($this->file, array($token));
    }

    private function execStringConcatenation($code)
    {
        $beforeErrorReporting = error_reporting();
        error_reporting(E_ALL);
        set_error_handler(array($this, 'handleEvalError'));

        $this->evalCode = $code;
        $path = eval($code);

        error_reporting($beforeErrorReporting);
        restore_error_handler();

        if (empty($path)) {
            throw new EvalException($code, $path);
        }

        return $path;
    }

    private function handleEvalError($errno, $errstr)
    {
        throw new EvalException($this->evalCode, "\$errno:{$errno}, \$errstr:{$errstr}");
    }

    public function string()
    {
        $str = '';
        foreach ($this->tokens as $token) {
            $str .= is_array($token) ? $token[1] : $token;
        }

        return $str;
    }

    public function type()
    {
        return $this->type;
    }

    public function getRequireFile()
    {
        return $this->requireFile;
    }

    private function dir()
    {
        $info = pathinfo($this->file);
        return $info['dirname'];
    }

    private function getRequireTokenString()
    {
        $token = $this->firstToken(array(T_REQUIRE, T_REQUIRE_ONCE, T_INCLUDE, T_INCLUDE_ONCE));

        return $token[1];
    }

    private function firstToken(array $tokenType)
    {
        if ($token = $this->allToken($tokenType)) {
            return $token[0];
        }

        return null;
    }

    private function allToken(array $tokenType)
    {
        $tokens = array();
        foreach ($this->tokens as $token) {
            if (is_array($token) && in_array($token[0], $tokenType)) {
                $tokens[] = $token;
            }
        }

        return $tokens;
    }

    public function getFixedString($requireBase, $constant = null)
    {
        if ($this->isFixable()) {
            $requireFile = $this->getRequireFile();
            if (Path::isAbsolute($requireFile)) {
                $relativePath = Path::makeRelative($requireFile, Path::canonicalize($requireBase));

                $requireString = $this->getRequireTokenString();
                $base = $constant ? $constant : "'$requireBase'";
                return "{$requireString} {$base} . '/{$relativePath}';";
            }
        }

        return null;
    }

    public function guessFromUnique($requiredFilePath)
    {
        if (!Path::isAbsolute($requiredFilePath)) {
            throw new \LogicException("{$requiredFilePath} is not absolute path.");
        }

        $this->type = 'unique';
        $this->requireFile = $requiredFilePath;
    }

    public function guessFromWorkingDir($requiredFilePath)
    {
        if (!Path::isAbsolute($requiredFilePath)) {
            throw new \LogicException("{$requiredFilePath} is not absolute path.");
        }

        $this->type = 'working_dir';
        $this->requireFile = $requiredFilePath;
    }

    public function guessFromIncludePath($requiredFilePath)
    {
        if (!Path::isAbsolute($requiredFilePath)) {
            throw new \LogicException("{$requiredFilePath} is not absolute path.");
        }

        $this->type = 'include_path';
        $this->requireFile = $requiredFilePath;
    }

    public function isFixable()
    {
        return ($this->type() == 'absolute' || $this->type() == 'unique' || $this->type() == 'working_dir' || $this->type() == 'include_path');
    }

    public function isRelative()
    {
        return $this->type() == 'relative';
    }

    public function isIncludeStatement()
    {
        return !is_null($this->firstToken(array(T_INCLUDE, T_INCLUDE_ONCE)));
    }
}
