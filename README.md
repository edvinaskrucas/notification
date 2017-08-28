# Notification package for Laravel4 / Laravel5

[![Build Status](https://travis-ci.org/edvinaskrucas/notification.png?branch=master)](https://travis-ci.org/edvinaskrucas/notification)

---

A simple notification management package for Laravel4.

---

* Notification containers
* Notification collections
* Notification messages
* Formats for notifications
* Flash / instant notifications
* Method chaining
* Message positioning

---

## Installation

Just place require new package for your laravel installation via composer.json

    "edvinaskrucas/notification": "5.*"

Then hit ```composer update```

### Version matrix

| Laravel Version       | Package version          |
| --------------------- | ------------------------ |
| = 5.4                 | 5.2.*                    |
| >= 5.1                | 5.1.*                    |
| >= 5.0, < 5.1         | 5.0.*                    |
| >= 4, < 5             | >= 2, <= 3               |

### Registering to use it with laravel

Add following lines to ```app/config/app.php```

ServiceProvider array

```php
\Krucas\Notification\NotificationServiceProvider::class,
```

Kernel middleware array (```must be placed after 'Illuminate\Session\Middleware\StartSession' middleware```)
```php
\Krucas\Notification\Middleware\NotificationMiddleware::class,
```

Now you are able to use it with Laravel4.

### Publishing config file

If you want to edit default config file, just publish it to your app folder.

    php artisan vendor:publish --provider="\Krucas\Notification\NotificationServiceProvider" --tag="config"

## Usage

### Default usage

Adding message to default container.
```php
\Krucas\Notification\Facades\Notification::success('Success message');
\Krucas\Notification\Facades\Notification::error('Error message');
\Krucas\Notification\Facades\Notification::info('Info message');
\Krucas\Notification\Facades\Notification::warning('Warning message');
```

### Containers

Containers allows you to set up different containers for different placeholders.

You can pass closure to modify containers, simply use this syntax showed below
```php
\Krucas\Notification\Facades\Notification::container('myContainer', function($container)
{
    $container->info('Test info message');
    $container->error('Error');
});
```

Also you can access container like this
```php
\Krucas\Notification\Facades\Notification::container('myContainer')->info('Info message');
```

Method chaining
```php
\Krucas\Notification\Facades\Notification::container('myContainer')->info('Info message')->error('Error message');
```

If you want to use default container just use ```null``` as container name. Name will be taken from config file.
```php
\Krucas\Notification\Facades\Notification::container()->info('Info message');
```

### Instant notifications (shown in same request)

Library supports not only flash messages, if you want to show notifications in same request just use
```php
\Krucas\Notification\Facades\Notification::successInstant('Instant success message');
```

### Custom single message format

Want a custom format for single message? No problem
```php
\Krucas\Notification\Facades\Notification::success('Success message', 'Custom format :message');
```

Also you can still pass second param (format), to format messages, but you can format individual messages as shown above.

### Add message as object

You can add messages as objects
```php
\Krucas\Notification\Facades\Notification::success(
    \Krucas\Notification\Facades\Notification::message('Sample text')
);
```

When adding message as object you can add additional params to message
```php
\Krucas\Notification\Facades\Notification::success(
    \Krucas\Notification\Facades\Notification::message('Sample text')->format(':message')
);
```

### Add message as closure

You can add messages by using a closure
```php
\Krucas\Notification\Facades\Notification::success(function (Message $message) {
    $message->setMessage('Sample text')->setPosition(1);
});
```

### Accessing first notification from container

You can access and show just first notification in container
```php
{!! \Krucas\Notification\Facades\Notification::container('myContainer')->get('success')->first() !!}
```

Accessing first notification from all types
```php
{!! \Krucas\Notification\Facades\Notification::container('myContainer')->all()->first() !!}
```

### Displaying notifications

To display all notifications in a default container you need to add just one line to your view file
```php
{!! \Krucas\Notification\Facades\Notification::showAll() !!}
```

When using ```showAll()``` you may want to group your messages by type, it can be done like this
```php
{!! \Krucas\Notification\Facades\Notification::group('info', 'success', 'error', 'warning')->showAll() !!}
```
This will group all your messages in group and output it, also you can use just one, two or three groups.

Manipulating group output on the fly
```php
\Krucas\Notification\Facades\Notification::addToGrouping('success')->removeFromGrouping('error');
```

Display notifications by type in default container, you can pass custom format
```php
{!! \Krucas\Notification\Facades\Notification::showError() !!}
{!! \Krucas\Notification\Facades\Notification::showInfo() !!}
{!! \Krucas\Notification\Facades\Notification::showWarning() !!}
{!! \Krucas\Notification\Facades\Notification::showSuccess(':message') !!}
```

Displaying notifications in a specific container with custom format.
```php
{!! \Krucas\Notification\Facades\Notification::container('myContainer')->showInfo(':message') !!}
```

Or you can just use blade extension
```php
@notification() // will render default container

@notification('custom') // will render 'custom' container
```

### Message positioning

There is ability to add message to certain position.
```php
// This will add message at 5th position
\Krucas\Notification\Facades\Notification::info(Notification::message('info')->position(5));
\Krucas\Notification\Facades\Notification::info(Notification::message('info2')->position(1);
```

### Clearing messages

You can clear all messages or by type.
```php
\Krucas\Notification\Facades\Notification::clearError();
\Krucas\Notification\Facades\Notification::clearWarning();
\Krucas\Notification\Facades\Notification::clearSuccess();
\Krucas\Notification\Facades\Notification::clearInfo();
\Krucas\Notification\Facades\Notification::clearAll();
```

### Add message and display it instantly in a view file

Want to add message in a view file and display it? Its very simple:

```php
{!! \Krucas\Notification\Facades\Notification::container('myInstant')
        ->infoInstant('Instant message added in a view and displayed!') !!}
```

You can also add multiple messages

```php
{!! \Krucas\Notification\Facades\Notification::container('myInstant')
        ->infoInstant('Instant message added in a view and displayed!')
        ->errorInstant('Error...') !!}
```
