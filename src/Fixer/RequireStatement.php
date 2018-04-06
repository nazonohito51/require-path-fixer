<?php
namespace RequirePathFixer\Fixer;

use RequirePathFixer\Exceptions\EvalException;
use Webmozart\PathUtil\Path;

class RequireStatement
{
    private $file;
    private $requireFile;
    private $tokens;
    private $type;

//    const TYPE = array('absolute', 'relative', 'guess', 'variable', 'unexpected');

    public function __construct($file, array $tokens)
    {
        $this->file = realpath($file);
        $this->tokens = $tokens;
        $this->type = $this->detectType();
        $this->requireFile = $this->detectRequireFile();
    }

    private function detectType()
    {
        if ($this->haveVariable() || $this->haveConstant()) {
            return 'variable';
        } elseif (is_null($this->getPathString())) {
            return 'unexpected';
        } elseif ($this->haveMagicConstant() && $this->getPathString()) {
            return 'absolute';
        } elseif (Path::isAbsolute($this->getPathString())) {
            return 'absolute';
        } elseif (Path::isRelative($this->getPathString())) {
            return 'relative';
        } else {
            return 'unexpected';
        }
    }

    private function detectRequireFile()
    {
        // Return a file path close to the absolute path as much as possible
        if ($this->type == 'variable' || $this->type == 'guess' || $this->type == 'unexpected') {
            return null;
        } else {
            $code = 'return ';
            foreach ($this->tokens as $token) {
                if (isset($token[0]) && in_array($token[0], array(T_REQUIRE, T_REQUIRE_ONCE, T_INCLUDE, T_INCLUDE_ONCE))) {
                    continue;
                } elseif (isset($token[0]) && $token[0] == T_FILE) {
                    $code .= "'" . $this->file . "'";
                } elseif (isset($token[0]) && $token[0] == T_DIR) {
                    $code .= "'" . $this->dir() . "'";
                } elseif (is_array($token)) {
                    $code .= $token[1];
                } else {
                    $code .= $token;
                }
            }

            $path = $this->execStringConcatenation($code);
            if (empty($path)) {
                $this->type = 'unexpected';
                throw new EvalException($code, $path);
            }

            return Path::canonicalize($path);
        }
    }

    private function execStringConcatenation($code)
    {
        return eval($code);
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
