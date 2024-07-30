<?php

namespace NAL\Event;

use InvalidArgumentException;
use NAL\DependenciesResolver\DependenciesResolver;
use NAL\Exception\ClassNotFound;
use NAL\Exception\InvalidListenerType;

class Event
{
    /**
     * Registered event listeners
     *
     * @var array
     */
    private array $listeners = [];

    /**
     * @var DependenciesResolver
     */
    private DependenciesResolver $resolver;

    /**
     * Deferred events
     *
     * @var array
     */
    private array $deferredEvents = [];

    /**
     * Subscribed classes list
     *
     * @var array
     */
    private array $subscribed = [];

    /**
     * Event constructor
     */
    public function __construct()
    {
        $this->resolver = new DependenciesResolver();
    }

    /**
     * Register an event listener
     *
     * @param string $event The name of the event to listen for
     * @param callable|array $listener The callback function to execute when the event is emitted
     * @param int $priority Priority to sort the listeners
     * @return Event
     */
    public function on(string $event, callable|array $listener, int $priority = 0): Event
    {
        $this->addListener($event, $listener, $priority);

        return $this;
    }

    /**
     * Register a one-time event listener
     *
     * @param string $event The name of the event to listen for
     * @param callable|array $listener The callback function to execute when the event is emitted
     * @param int $priority Priority to sort the listeners
     * @return Event
     */
    public function once(string $event, callable|array $listener, int $priority = 0): Event
    {
        $this->addListener($event, $listener, $priority, true);

        return $this;
    }

    /**
     * Get all the listeners for a given event or all of listeners
     *
     * @param string|null $event (optional) The name of the event
     * @return mixed
     */
    public function getListeners(string $event = null): mixed
    {
        if ($event) {

            $this->eventCannotBeEmpty($event);

            return $this->listeners[$event] ?? [];
        }

        return $this->listeners;
    }

    /**
     * Remove all listeners for a given event
     *
     * @param string|null $event (optional) The name of the event
     * @return void
     */
    public function removeListeners(?string $event = null): void
    {
        if ($event) {

            $this->eventCannotBeEmpty($event);

            unset($this->listeners[$event]);
        } else {
            $this->listeners = [];
        }
    }

    /**
     * Remove the given listener from given event
     *
     * @param string $event The name of the event
     * @param callable $listener The listener to remove
     * @return void
     */
    public function removeListener(string $event, callable $listener): void
    {
        $this->eventCannotBeEmpty($event);

        if (isset($this->listeners[$event])) {
            foreach ($this->listeners[$event] as $priority => $listeners) {
                foreach ($listeners as $key => $data) {
                    if (($key === 'once' && ($index = array_search($listener, $data, true)) !== false) ||
                        ($index = array_search($listener, $listeners, true)) !== false) {

                        if ($key === 'once') {
                            unset($this->listeners[$event][$priority][$key][$index]);
                        } else {
                            unset($this->listeners[$event][$priority][$key]);
                        }

                        if (empty($this->listeners[$event][$priority][$key])) {
                            unset($this->listeners[$event][$priority][$key]);
                        }
                        if (empty($this->listeners[$event][$priority])) {
                            unset($this->listeners[$event][$priority]);
                        }

                        break 2;
                    }
                }
            }
        }
    }

    /**
     * Emit an event
     *
     * @param string $event The name of the event to emit
     * @param mixed ...$args Parameters to pass to the event listeners
     * @return void
     */
    public function emit(string $event, mixed ...$args): void
    {
        $this->eventCannotBeEmpty($event);

        if (isset($this->listeners[$event])) {

            ksort($this->listeners[$event]);

            foreach ($this->listeners[$event] as $priority => $listeners) {
                foreach ($listeners as $key => $data) {

                    if ($key === 'once') {
                        unset($this->listeners[$event][$priority][$key]);

                        foreach ($data as $listener) {
                            $this->resolve($listener, ...$args);
                        }
                    } else {
                        $this->resolve($data, ...$args);
                    }
                }
            }
        }
    }

    /**
     * Defer an event to be emitted later
     *
     * @param string $event The name of the event to defer
     * @param mixed ...$args Parameters to pass to the event listeners
     * @return Event
     */
    public function defer(string $event, mixed ...$args): Event
    {
        $this->eventCannotBeEmpty($event);

        $this->deferredEvents[] = ["event" => $event, "args" => $args];

        return $this;
    }

    /**
     * Dispatch all deferred events
     *
     * @return void
     */
    public function dispatch4deferred(): void
    {
        if (!empty($this->deferredEvents)) {
            foreach ($this->deferredEvents as $index => $event) {
                $this->emit($event["event"], ...$event["args"]);
            }
        }
    }

    /**
     * Subscribe to events using an EventSubscriber
     *
     * @param EventSubscriber $subscriber The subscriber to register
     * @return Event
     * @throws InvalidListenerType If the subscriber event listeners are invalid
     * @throws ClassNotFound If a specified subscriber class does not exist
     */
    public function subscribe(EventSubscriber $subscriber): Event
    {
        $this->subscribeAction("subscribe", $subscriber);

        return $this;
    }

    /**
     * Unsubscribe from events using an EventSubscriber
     *
     * @param EventSubscriber $subscriber The subscriber to unregister
     * @return Event
     */
    public function unsubscribe(EventSubscriber $subscriber): Event
    {
        if (in_array($subscriber::class, $this->subscribed)) {
            $this->subscribeAction("unsubscribe", $subscriber);
        }

        return $this;
    }

    /**
     * Handle subscription or unsubscription actions for an EventSubscriber
     *
     * @param string $type The type of action ("subscribe" or "unsubscribe")
     * @param EventSubscriber $subscriber The subscriber to register or unregister
     * @return void
     */
    private function subscribeAction(string $type, EventSubscriber $subscriber): void
    {
        $class  = $subscriber::class;

        if ($type === "subscribe") {
            $this->subscribed[] = $class;
        } else {
            foreach ($this->subscribed as $index => $subscribed) {
                if ($class === $subscribed) {
                    unset($this->subscribed[$index]);
                }
            }
        }

        $events = $subscriber->getEvents();

        if (!empty($events)) {
            foreach ($events as $event => $listeners) {
                if ($type === "subscribe") {
                    if (is_array($listeners)) {
                        foreach ($listeners as $listener) {
                            $this->on($event, [$class, $listener]);
                        }
                    } else {
                        $this->on($event, [$class, $listeners]);
                    }
                } else {
                    $this->removeListeners($event);
                }
            }
        }
    }

    /**
     * Create an empty array for a given event if it does not exist
     *
     * @param array &$holder The array holder
     * @param string $event The event name
     * @return void
     */
    private function createEmptyArrOnNull(array &$holder, string $event): void
    {
        if (!isset($holder[$event])) {
            $holder[$event] = [];
        }
    }

    /**
     * Resolve the event to emit
     *
     * @param $listener
     * @param mixed ...$args
     * @return void
     */
    private function resolve($listener, ...$args): void
    {
        if (is_array($listener)) {

            $this->check($listener);

            if (empty($args)) {
                $this->resolver->method($listener[1], $listener[0]);
            } else {
                $this->createClass($listener[0]);

                call_user_func($listener, ...$args);
            }
        }  else {
            if (empty($args)) {
                $this->resolver->callback($listener);
            } else {
                call_user_func($listener, ...$args);
            }
        }
    }

    /**
     * Instantiate a class and assign it to the provided variable
     *
     * @param mixed $class The class name to instantiate
     * @return void
     */
    private function createClass(mixed &$class): void
    {
        if (is_string($class)) {
            $class = new $class();
        }
    }

    /**
     * Validate the event listener
     *
     * @param array $listener The event listener to validate
     * @return void
     * @throws InvalidListenerType If the listener array is invalid
     * @throws ClassNotFound() If the specified class does not exist
     */
    private function check(array $listener): void
    {
        if (empty($listener) || !isset($listener[0]) || !isset($listener[1])) {
            throw new InvalidListenerType();
        }

        if (!class_exists($listener[0])) {
            throw new ClassNotFound($listener[0]);
        }
    }

    /**
     * Add the listeners
     *
     * @param string $event The name of the event to listen for
     * @param callable|array $listener The callback function to execute when the event is emitted
     * @param int $priority Priority to sort the listeners
     * @param bool $once Is listener just for once
     * @return void
     */
    private function addListener(string $event, callable|array $listener, int $priority = 0, bool $once = false): void
    {
        $this->eventCannotBeEmpty($event);

        $this->createEmptyArrOnNull($this->listeners, $event);

        if ($once) {
            $this->listeners[$event][$priority]['once'][] = $listener;
        } else {
            $this->listeners[$event][$priority][] = $listener;
        }
    }

    /**
     * Throw `InvalidArgumentException` on empty string of event name
     *
     * @param $event
     * @return void
     */
    private function eventCannotBeEmpty($event): void
    {
        if ($event === '') {
            throw new InvalidArgumentException('Event name cannot be empty.');
        }
    }
}
