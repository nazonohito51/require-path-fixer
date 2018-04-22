<?php
namespace RequirePathFixer\Fixer;

class PhpFile
{
    private $path;
    private $tokens;
    private $replacement = array();

    /**
     * @var RequireStatement[]
     */
    private $requireStatements = array();

    public function __construct($path, array $replacement = array())
    {
        $this->path = realpath($path);
        $this->tokens = token_get_all(file_get_contents($this->path));
        $this->replacement = $replacement;
        $this->searchRequireStatements();
    }

    public function path()
    {
        return $this->path;
    }

    private function searchRequireStatements()
    {
        for ($index = 0; $index < count($this->tokens); $index++) {
            if (is_array($this->tokens[$index]) && in_array($this->tokens[$index][0], array(T_REQUIRE, T_REQUIRE_ONCE, T_INCLUDE, T_INCLUDE_ONCE))) {
                $start = $index;
                $end = null;
                for (; $index < count($this->tokens); $index++) {
                    if ($this->tokens[$index] == ';' || (isset($this->tokens[$index][0]) && $this->tokens[$index][0] == T_CLOSE_TAG)) {
                        $end = ++$index;
                        break;
                    }
                }

                if ($start && $end) {
                    $this->requireStatements[] = new RequireStatement(
                        $this->path,
                        array_slice($this->tokens, $start, $end - $start),
                        $this->replacement
                    );
                }
            }
        }
    }

    public function getRequireStatements()
    {
        return $this->requireStatements;
    }

    public function getContents()
    {
        $str = '';

        foreach ($this->tokens as $token) {
            if (is_array($token)) {
                $str .= $token[1];
            } else {
                $str .= $token;
            }
        }

        return $str;
    }

    public function getFixedContents($requireBase, $constant = null)
    {
        $content = file_get_contents($this->path);

        foreach ($this->requireStatements as $requireStatement) {
            if ($fixedString = $requireStatement->getFixedString($requireBase, $constant)) {
                $content = str_replace($requireStatement->string(), $fixedString, $content);
            }
        }

        return $content;
    }
}
