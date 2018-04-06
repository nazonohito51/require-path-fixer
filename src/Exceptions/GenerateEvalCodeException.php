<?php
namespace RequirePathFixer\Exceptions;

class GenerateEvalCodeException extends \RuntimeException
{
    private $phpFile;
    private $token;

    public function __construct($phpFile, $token)
    {
        $this->phpFile = $phpFile;
        $this->token = $token;
        parent::__construct('Generating eval code is failed.');
    }
}
