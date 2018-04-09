<?php
namespace RequirePathFixer\Exceptions;

class GenerateEvalCodeException extends \RuntimeException
{
    private $phpFile;
    private $tokens;

    public function __construct($phpFile, $tokens)
    {
        $this->phpFile = $phpFile;
        $this->tokens = $tokens;
        parent::__construct('Generating eval code is failed.');
    }
}
