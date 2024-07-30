<?php

namespace NAL\Event;

/**
 * Interface EventSubscriber
 *
 * Defines the contract for event subscribers.
 * Event subscribers are classes that declare which events they are interested in and their associated event listeners.
 *
 */
interface EventSubscriber
{
    /**
     * Get the events and their corresponding listeners.
     *
     * @return array An associative array where the key is the event name and the value is either a single method name
     *               as a string, or an array of method names to be called when the event is emitted.
     *
     * Example:
     *
     *               [
     *                   "eventName" => "methodName",
     *                   "anotherEvent" => ["methodOne", "methodTwo"]
     *               ]
     */
    public function getEvents(): array;
}
