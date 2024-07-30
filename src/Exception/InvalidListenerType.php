<?php

namespace NAL\Exception;

class InvalidListenerType extends \InvalidArgumentException
{
    public function __construct()
    {
        parent::__construct(sprintf("Incorrect array format. Array must be [%s, 'method']", "\\namespace\controller"));
    }
}
