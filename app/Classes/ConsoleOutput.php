<?php

namespace App\Classes;

use Symfony\Component\Console\Output\ConsoleOutput as BaseConsoleOutput;

/**
 * Class ConsoleOutput
 * @package App\Classes
 */
class ConsoleOutput extends BaseConsoleOutput
{
    /**
     * @param string $message
     */
    public function error(string $message)
    {
        $this->showStyled($message, 'error');
    }

    /**
     * @param string $message
     */
    public function info(string $message)
    {
        $this->showStyled($message, 'info');
    }

    /**
     * @param string $message
     * @param bool $style
     */
    private function showStyled(string $message, $style = false)
    {
        $styled = $style ? "<$style>$message</$style>" : $message;

        $this->writeln($styled);
    }
}
