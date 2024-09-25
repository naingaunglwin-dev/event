# Changelog

## v1.0.1

### Added

- ### **methods**
  - `subscribe`: Allows you to register a custom event class as a subscriber. The class must implement \NAL\Event\EventSubscriber. This method enables your class to receive events and handle them accordingly.
  - `unsubscribe`: Removes a previously registered event subscriber. Use this method to unregister your class from receiving events.
  - `defer`: Introduces a mechanism for deferring event dispatching. This allows you to postpone the execution of events until a later time.
  - `dispatch4deferred`: Dispatches events that were deferred using the defer method. This method ensures that all deferred events are processed as intended.

- ### Enhanced Event Listener Management
  - The on and once methods now support accepting arrays of listeners. This allows you to register multiple listeners in a single call, simplifying the process of adding event handlers.

- ### Improvements
  - **Event Subscription Management**
    - You can now `subscribe` and `unsubscribe` event classes more easily, providing greater flexibility in managing event listeners and subscribers.
  - **Deferred Event Handling**
    - The new `defer` and `dispatch4deferred` methods enable more sophisticated event handling scenarios, allowing for better control over when and how events are processed.

### **Review [README.md](README.md) for more detail**
