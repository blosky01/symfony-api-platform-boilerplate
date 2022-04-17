<?php

namespace App\Event;

use Symfony\Contracts\EventDispatcher\Event;

abstract class AbstractEvent extends Event
{
    protected $data;

    public function __construct($data = null)
    {
        $this->data = $data;
    }

    public function getData(): mixed
    {
        if($this->data != null) {
            return $this->data;
        }

        return false;
    }
}