<?php

namespace NALEventTests;

use NAL\Event\Event;
use PHPUnit\Framework\TestCase;

class EventTest extends TestCase
{
    public function testListenersAreCalledInOrderOfPriority()
    {
        $event = new Event();

        $output = "";

        $event->on("test", function () use (&$output) {
            $output .= "low priority ";
        }, 10);

        $event->on("test", function () use (&$output) {
            $output .= "high priority ";
        }, 1);

        $event->emit("test");

        $this->assertSame("high priority low priority ", $output);
    }

    public function testRemoveSpecificListener()
    {
        $event = new Event();

        $output = "";

        $listener = function () use (&$output) {
            $output .= "listener ";
        };

        $event->on("test", $listener);

        $event->removeListener("test", $listener);

        $event->emit("test");

        $this->assertSame("", $output);
    }

    public function testOnceListener()
    {
        $event = new Event();

        $output = "";

        $event->once("test", function () use (&$output) {
            $output .= "once ";
        });

        $event->emit("test");
        $event->emit("test");

        $this->assertSame("once ", $output);
    }


    public function testRemoveAllListenersForEvent()
    {
        $event = new Event();

        $output = "";

        $event->on("test", function () use (&$output) {
            $output .= "listener ";
        });

        $event->removeListeners('test');

        $event->emit("test");

        $this->assertSame("", $output);
    }

    public function testRemoveAllListeners()
    {
        $event = new Event();

        $output = "";

        $event->on("test", function () use (&$output) {
            $output .= "listener ";
        });

        $event->removeListeners();

        $event->emit("test");

        $this->assertSame("", $output);
    }

    public function testRemoveOnceListener()
    {
        $event = new Event();

        $output = "";

        $listener = function () use (&$output) {
            $output .= "listener ";
        };

        $event->once("test", $listener);

        $event->removeListener("test", $listener);

        $event->emit("test");

        $this->assertSame("", $output);
    }

    public function testEmitWithArguments()
    {
        $event = new Event();

        $output = "";

        $event->on("test", function ($title, $author) use (&$output) {
            $output .= "$title : $author";
        });

        $event->emit("test", "Event", "John Doe");

        $this->assertSame("Event : John Doe", $output);
    }

    public function testGetSpecificEventListeners()
    {
        $event = new Event();

        $output = "";

        $listener = function () use (&$output) {
            $output .= "listener ";
        };

        $event->on("test", $listener, 1);

        $this->assertSame([1 => [$listener]], $event->getListeners("test"));
    }

    public function testGetAllEventListeners()
    {
        $event = new Event();

        $output = "";

        $listener1 = function () use (&$output) {
            $output .= "listener1 ";
        };

        $listener2 = function () use (&$output) {
            $output .= "listener2 ";
        };

        $event->on("test", $listener1, 1);

        $event->on("test", $listener2, 2);

        $this->assertSame([1 => [$listener1], 2 => [$listener2]], $event->getListeners("test"));
    }

    public function testGetAllListeners()
    {
        $event = new Event();

        $output = "";

        $listener1 = function () use (&$output) {
            $output .= "listener1 ";
        };

        $listener2 = function () use (&$output) {
            $output .= "listener2 ";
        };

        $event->on("test", $listener1, 1);

        $event->on("test", $listener2, 2);

        $this->assertSame(
            [
                "test" => [
                    1 => [$listener1],
                    2 => [$listener2]
                ]
            ],
            $event->getListeners()
        );
    }

    public function testExceptionOnEmptyStringOfEventName()
    {
        $this->expectException(\InvalidArgumentException::class);

        $event = new Event();

        $event->on("", function () {
            echo "Hello World";
        });
    }
}
