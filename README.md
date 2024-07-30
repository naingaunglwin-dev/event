<div align="center">

# Simple Event Library
![Status](https://img.shields.io/badge/tests-passing-lemon)
![Status](https://img.shields.io/badge/coverage-100%25-green)
![Status](https://img.shields.io/badge/license-MIT-blue)

</div>

## Contributing
- This is an open-source library, and contributions are welcome.
- If you have any suggestions, bug reports, or feature requests, please open an issue or submit a pull request on the project repository.

## Requirement
- **PHP** version 8.2 or newer is required

## Installation & Setup
- You can download using composer.
- If you don't have composer, install [composer](https://getcomposer.org/download/) first.
- create file `composer.json` at your project root directory.
- Add this to `composer.json`
```php
{
  "require": {
    "naingaunglwin-dev/event": "^1.0"
  }
}
```
- Run the following command in your terminal from the project's root directory:
```bash
composer install
```

If you already have `composer.json` file in your project, just run this command in your terminal,
```bash
composer require naingaunglwin-dev/event
```

## Usage
```php
<?php

require_once "vendor/autoload.php";

use NAL\Event\Event;

$event = new Event();

// Define the event
$event->on("message", function () {
    echo "Message Sent!";
});

// emit the event
$event->emit("message"); // Message Sent!

```
- Define event listeners with arguments
```php
// Define the event with arguments
$event->on("message", function ($at) {
    echo "Sent message at $at";
});

// emit the event with arguments
$event->emit("message", date('Y-m-d H:i:s'));

```
- Define event listeners with Class arguments
```php
$event->on("message", function (User $user) {
    echo $user->notify("message is sent");
});

$event->emit("message"); // You don't have to pass the argument if arguments are classes
```

- Define event listeners with priority
```php
$event->on("message", function () {
    echo "Message Sent!";
}, 1);

$event->on("message", function () {
    $notify->user();
}, 2);

$event->emit("message"); // The higher priority listeners (lower numerical values) will be executed first.

```
- Define the event listeners just one time
```php
$event->once("message", function () {
    echo "Message is sent";
});

$event->emit("message"); // "Message is sent"
$event->emit("message"); // "" (Empty string)

```

- Get the event listeners
```php
// Get all listeners for a specific event
$listeners = $event->getListeners("message");

// Get all listeners for all events
$allListeners = $event->getListeners();
```
- Remove a Specific Listener
```php
$listener = function () {
    echo "This message will be removed.";
};

// Add the listener
$event->on("message", $listener);

// Remove the specific listener
$event->removeListener("message", $listener);

// Try emitting the event
$event->emit("message"); // No output since the listener has been removed
```
- Remove All Listeners for a Specific Event
```php
// Add multiple listeners to the event
$event->on("message", function () {
    echo "First listener";
});

$event->on("message", function () {
    echo "Second listener";
});

// Remove all listeners for the event
$event->removeListeners("message");

// Try emitting the event
$event->emit("message"); // No output since all listeners have been removed
```
- Remove All Listeners for All Events
```php
// Add listeners to different events
$event->on("message", function () {
    echo "Message listener";
});

$event->on("notification", function () {
    echo "Notification listener";
});

// Remove all listeners for all events
$event->removeListeners();

// Try emitting the events
$event->emit("message"); // No output since all listeners have been removed
$event->emit("notification"); // No output since all listeners have been removed
```

## Available since version v1.0.1 and above
- **(v1.0.1 - Still under development, not release yet)**

- Defer the event to emit later
```php

$event->on("message", function () {
    echo "The first message.\n";
});

$event->on("message", function () {
    echo "The second message.\n";
});

if ($shouldMessage) {
    $event->defer("message");
} else {
    // Other processes
}

// Other processes

// Dispatch all deferred events
$event->dispatch4deferred(); // If condition meet,
                             // "The first message."
                             // "The second message."
```

- Add Subscriber to event
```php

// Subscriber class must implement to `\NAL\Event\EventSubscriber`

class Conversation implements \NAL\Event\EventSubscriber {

    /**
    * @inheritDoc
    */
    public function getEvents(): array
    {
        return [
            "morning"   => "greeting",
            "afternoon" => "greeting",
            "goodbye"   => ["goodbye", "end"]
        ];
    }

    public function greeting($time, $name)
    {
        echo "Good $time, $name!\n";
    }
    
    public function goodbye($name)
    {
        echo "Goodbye, $name!\n";
    }
    
    public function end()
    {
        echo "Have a nice day!\n";
    }
}

$event = new \NAL\Event\Event();

$event->subscribe(new Conversation());

$event->emit("morning", "Morning","David"); // "Good Morning, David!"

$event->emit("afternoon", "Afternoon", "John"); // "Good Afternoon, John!"

$event->emit("goodbye", "Alex"); // "Goodbye, Alex!"
                                 // "Have a nice day!"

// You can also unsubscribe the subscribed listener
$event->unsubscribe(new Conversation());

```
