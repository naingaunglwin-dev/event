<?php

namespace NALEventTests;

class TestSubscriber implements \NAL\Event\EventSubscriber
{
    public function getEvents(): array
    {
        return [
            'testEvent' => 'onTestEvent'
        ];
    }

    public function onTestEvent()
    {
        echo "Event handled";
    }
}
