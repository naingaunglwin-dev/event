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

    public function testSubscribeMethod()
    {
        $event = new Event();

        // Use a concrete class as the subscriber
        $subscriber = new TestSubscriber();

        // Subscribe the test subscriber
        $event->subscribe($subscriber);

        // Capture the output when the event is emitted
        ob_start();
        $event->emit('testEvent');
        $output = ob_get_clean();

        // Assert that the correct output was produced
        $this->assertSame("Event handled", $output);
    }

    public function testDispatch4DeferredMethod()
    {
        $event = new Event();

        // Output string to capture the results of deferred events
        $output = "";

        $event->on('testEvent', function ($arg) use (&$output) {
            $output .= $arg;
        });

        // Defer two events with their corresponding callbacks
        $event->defer('testEvent', "First deferred ");

        $event->defer('testEvent', "Second deferred ");

        // Assert that deferred events have not executed yet
        $this->assertSame("", $output);

        // Now dispatch the deferred events
        $event->dispatch4deferred();

        // Check that both deferred events executed in order
        $this->assertSame("First deferred Second deferred ", $output);
    }

    public function testUnsubscribeMethod()
    {
        $event = new Event();
        $subscriber = new TestSubscriber();

        // Subscribe the test subscriber
        $event->subscribe($subscriber);

        // Emit and check output to confirm the subscriber was called
        ob_start();
        $event->emit('testEvent');
        $output = ob_get_clean();
        $this->assertSame("Event handled", $output);

        // Now, unsubscribe the subscriber
        $event->unsubscribe($subscriber);

        // Emit the event again, but this time, since we unsubscribed, no output should occur
        ob_start();
        $event->emit('testEvent');
        $outputAfterUnsubscribe = ob_get_clean();

        // Assert that no output was produced after unsubscribing
        $this->assertSame("", $outputAfterUnsubscribe);
    }
}
