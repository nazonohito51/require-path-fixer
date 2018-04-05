<?php
namespace RequirePathFixer\Exceptions;

class EvalException extends \RuntimeException
{
    private $evalCode;
    private $return;

    public function __construct($evalCode, $return)
    {
        $this->evalCode = $evalCode;
        $this->return = $return;
        parent::__construct('Returned unexpected value from eval().');
    }

    public function __toString()
    {
        return "message:{$this->getMessage()}, code:{$this->code}, return:{$this->return}";
    }
}
