<?php

namespace Rk\Threadwell\Core;

class HttpException extends \RuntimeException
{
    public function __construct(int $status = 404, string $message = '')
    {
        parent::__construct($message, $status);
    }

    public function status(): int
    {
        return $this->getCode();
    }
}
