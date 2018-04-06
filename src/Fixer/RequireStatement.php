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

//    const TYPE = array('absolute', 'relative', 'guess', 'variable', 'unexpected');

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
            foreach ($this->tokens as $token) {
                if (isset($token[0]) && in_array($token[0], array(T_REQUIRE, T_REQUIRE_ONCE, T_INCLUDE, T_INCLUDE_ONCE))) {
                    continue;
                } elseif (isset($token[0]) && $token[0] == T_FILE) {
                    $code .= "'" . $this->file . "'";
                } elseif (isset($token[0]) && $token[0] == T_DIR) {
                    $code .= "'" . $this->dir() . "'";
                } elseif (isset($token[0]) && $token[0] == T_STRING && $token[1] != 'dirname') {
                    $code .= $this->replaceToken($token);
                } elseif (isset($token[0]) && $token[0] == T_VARIABLE) {
                    $code .= $this->replaceToken($token);
                } elseif (is_array($token)) {
                    $code .= $token[1];
                } else {
                    $code .= $token;
                }
            }

            $this->requireFile = Path::canonicalize($this->execStringConcatenation($code));
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

    private function replaceToken(array $token)
    {
        if (isset($this->replacements[$token[1]])) {
            return "'" . $this->replacements[$token[1]] . "'";
        }

        throw new GenerateEvalCodeException($this->file, $token);
    }

    private function execStringConcatenation($code)
    {
        $path = eval($code);
        if (empty($path)) {
            throw new EvalException($code, $path);
        }

        return $path;
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

    private function getPathString()
    {
        if ($token = $this->firstToken(array(T_CONSTANT_ENCAPSED_STRING))) {
            return preg_replace('/["\']/', '', $token[1]);
        }

        return null;
    }

    private function getRequireString()
    {
        $token = $this->firstToken(array(T_REQUIRE, T_REQUIRE_ONCE, T_INCLUDE, T_INCLUDE_ONCE));

        return $token[1];
    }

    private function haveVariable()
    {
        return !is_null($this->firstToken(array(T_VARIABLE)));
    }

    private function haveConstant()
    {
        $stringTokens = $this->allToken(array(T_STRING));

        foreach ($stringTokens as $stringToken) {
            if ($stringToken[1] != 'dirname') {
                return true;
            }
        }
        return false;
    }

    private function haveMagicConstant()
    {
        return $this->haveDirname() || $this->haveDir();
    }

    private function haveDir()
    {
        return $this->firstToken(array(T_DIR)) ? true : false;
    }

    private function haveDirname()
    {
        $dirnameToken = $this->firstToken(array(T_STRING));
        $fileToken = $this->firstToken(array(T_FILE));
        return (
            $dirnameToken &&
            $dirnameToken[1] == 'dirname' &&
            $fileToken
        ) ? true : false;
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
        if ($this->type() == 'absolute' || $this->type() == 'guess') {
            $requireFile = $this->getRequireFile();
            if (Path::isAbsolute($requireFile)) {
                $relativePath = Path::makeRelative($requireFile, $requireBase);

                $requireString = $this->getRequireString();
                $base = $constant ? $constant : "'$requireBase'";
                return "{$requireString} {$base} . '/{$relativePath}';";
            }
        }

        return null;
    }

    public function guess($requiredFilePath)
    {
        $this->type = 'guess';
        $this->requireFile = $requiredFilePath;
    }
}
