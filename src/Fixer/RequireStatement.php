<?php
namespace RequirePathFixer\Fixer;

use Webmozart\PathUtil\Path;

class RequireStatement
{
    private $filePath;
    private $tokenIndex;
    private $tokens;

    public function __construct($filePath, $tokenIndex, array $tokens)
    {
        $this->filePath = $filePath;
        $this->tokenIndex = $tokenIndex;
        $this->tokens = $tokens;
    }

    public function string()
    {
        $str = '';
        foreach ($this->tokens as $token) {
            $str .= is_array($token) ? $token[1] : $token;
        }

        return $str;
    }

    public function getIndex()
    {
        return $this->tokenIndex;
    }

    public function getTokenCount()
    {
        return count($this->tokens);
    }

    public function getRequiredFilePath()
    {
        // Return a file path close to the absolute path as much as possible
        if ($this->haveMagicConstant()) {
            return realpath($this->dir() . $this->getPathStringToken());
        } elseif ($token = $this->firstToken(array(T_CONSTANT_ENCAPSED_STRING))) {
            return $this->getPathStringToken();
        }

        throw new \LogicException();
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
        return ($dirnameToken && $dirnameToken[1]== 'dirname') ? true : false;
    }

    private function firstToken(array $searchTokens)
    {
        foreach ($this->tokens as $token) {
            if (is_array($token) && in_array($token[0], $searchTokens)) {
                return $token;
            }
        }

        return false;
    }

    public function getFixedStatement($requireBase, $constant = null)
    {
        $requiredFilePath = $this->getRequiredFilePath();
        if (Path::isAbsolute($requiredFilePath)) {
            $relativePath = Path::makeRelative($requiredFilePath, $requireBase);

            $requireToken = $this->getRequireToken();
            $base = $constant ? $constant : "'$requireBase'";
            return "{$requireToken} {$base} . '/{$relativePath}';";
        }

        return null;
    }

    private function getRequireToken()
    {
        return $this->tokens[0][1];
    }
}
