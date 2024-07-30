<?php

namespace NAL\Exception;

class ClassNotFound extends \RuntimeException
{
    public function __construct(string $class = "", int $code = 0)
    {
        parent::__construct("Class: $class is not found:", $code);
    }
}
