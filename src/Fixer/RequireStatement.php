<?php
namespace RequirePathFixer\Fixer;

use RequirePathFixer\Exceptions\EvalException;
use Webmozart\PathUtil\Path;

class RequireStatement
{
    private $filePath;
    private $requiredFilePath;
    private $tokens;
    private $type;

//    const TYPE = array('absolute', 'relative', 'guess', 'variable', 'unexpected');

    public function __construct($filePath, array $tokens)
    {
        $this->filePath = $filePath;
        $this->tokens = $tokens;
        $this->type = $this->detectType();
        $this->requiredFilePath = $this->detectRequiredFilePath();
    }

    private function detectType()
    {
        if ($this->haveVariable() || $this->haveConstant()) {
            return 'variable';
        } elseif (is_null($this->getPathStringToken())) {
            return 'unexpected';
        } elseif ($this->haveMagicConstant() && $this->getPathStringToken()) {
            return 'absolute';
        } elseif (Path::isAbsolute($this->getPathStringToken())) {
            return 'absolute';
        } elseif (Path::isRelative($this->getPathStringToken())) {
            return 'relative';
        } else {
            return 'unexpected';
        }
    }

    private function detectRequiredFilePath()
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
                    $code .= "'" . $this->filePath . "'";
                } elseif (isset($token[0]) && $token[0] == T_DIR) {
                    $code .= "'" . $this->dir() . "'";
                } elseif (is_array($token)) {
                    $code .= $token[1];
                } else {
                    $code .= $token;
                }
            }

            $path = eval($code);
            if (empty($path)) {
                $this->type = 'unexpected';
                throw new EvalException($code, $path);
            }

            return Path::canonicalize($path);
        }
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

    public function getRequiredFilePath()
    {
        return $this->requiredFilePath;
    }

    private function dir()
    {
        $info = pathinfo($this->filePath);
        return $info['dirname'];
    }

    private function getPathStringToken()
    {
        if ($token = $this->firstToken(array(T_CONSTANT_ENCAPSED_STRING))) {
            return preg_replace('/["\']/', '', $token[1]);
        }

        return null;
    }

    private function haveVariable()
    {
        return !is_null($this->firstToken(array(T_VARIABLE)));
    }

    private function haveConstant()
    {
        $stringTokens = $this->allToken(array(T_STRING));

        $haveConstant = false;
        foreach ($stringTokens as $stringToken) {
            if ($stringToken[1] != 'dirname') {
                $haveConstant = true;
            }
        }
        return $haveConstant;
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

    private function firstToken(array $searchTokens)
    {
        foreach ($this->tokens as $token) {
            if (is_array($token) && in_array($token[0], $searchTokens)) {
                return $token;
            }
        }

        return null;
    }

    private function allToken(array $searchTokens)
    {
        $tokens = array();
        foreach ($this->tokens as $token) {
            if (is_array($token) && in_array($token[0], $searchTokens)) {
                $tokens[] = $token;
            }
        }

        return $tokens;
    }

    public function getFixedStatement($requireBase, $constant = null)
    {
        if ($this->type() == 'absolute' || $this->type() == 'guess') {
            $requiredFilePath = $this->getRequiredFilePath();
            if (Path::isAbsolute($requiredFilePath)) {
                $relativePath = Path::makeRelative($requiredFilePath, $requireBase);

                $requireToken = $this->getRequireToken();
                $base = $constant ? $constant : "'$requireBase'";
                return "{$requireToken} {$base} . '/{$relativePath}';";
            }
        }

        return null;
    }

    private function getRequireToken()
    {
        return $this->tokens[0][1];
    }

    public function guess($requiredFilePath)
    {
        $this->type = 'guess';
        $this->requiredFilePath = $requiredFilePath;
    }
}
