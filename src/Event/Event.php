<?php

namespace NAL\Event;

use InvalidArgumentException;
use NAL\DependenciesResolver\DependenciesResolver;

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
     * @param callable $listener The callback function to execute when the event is emitted
     * @param int $priority Priority to sort the listeners
     * @return void
     */
    public function on(string $event, callable $listener, int $priority = 0): void
    {
        $this->addListener($event, $listener, $priority);
    }

    /**
     * Register a one-time event listener
     *
     * @param string $event The name of the event to listen for
     * @param callable $listener The callback function to execute when the event is emitted
     * @param int $priority Priority to sort the listeners
     * @return void
     */
    public function once(string $event, callable $listener, int $priority = 0): void
    {
        $this->addListener($event, $listener, $priority, true);
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
     * Resolve the event to emit
     *
     * @param $listener
     * @param mixed ...$args
     * @return void
     */
    private function resolve($listener, ...$args): void
    {
        if (empty($args)) {
            $this->resolver->callback($listener);
        } else {
            call_user_func($listener, ...$args);
        }
    }

    /**
     * Add the listeners
     *
     * @param string $event The name of the event to listen for
     * @param callable $listener The callback function to execute when the event is emitted
     * @param int $priority Priority to sort the listeners
     * @param bool $once Is listener just for once
     * @return void
     */
    private function addListener(string $event, callable $listener, int $priority = 0, bool $once = false): void
    {
        $this->eventCannotBeEmpty($event);

        if (!isset($this->listeners[$event])) {
            $this->listeners[$event] = [];
        }

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
