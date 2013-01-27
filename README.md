# Notification package for Laravel4

---

A simple notification management package for Laravel4.

---

* Notification containers
* Notification collections
* Notification messages
* Formats for notifications
* Flashable notifications
* Method chaining

---

## Installation

Just place require new package for your laravel installation via composer.json

    "edvinaskrucas/notification": "dev-master"

Then hit ```composer update```

### Registering to use it with laravel

Add following lines to ```app/config/app.php```

ServiceProvider array

```php
'Notification\NotificationServiceProvider'
```

Alias array
```php
'Notification' => 'Notification\Facades\Notification'
```

Now you are able to use it with Laravel4.

### Publishing config file

If you want to edit default config file, just publish it to your app folder.

    php artisan config:publish edvinaskrucas/notification

## Usage

### Default usage

Adding message to default container.
```php
Notification::success('Success message');
Notification::error('Error message');
Notification::info('Info message');
Notification::warning('Warning message');
```

### Containers

Containers allows you to set up different containers for different placeholders.

You can pass closure to modify containers, simply use this syntax showed below
```php
Notification::container('myContainer', function($container)
{
    $container->info('Test info message');
    $container->error('Error');
});
```

Also you can access container like this
```php
Notification::container('myContainer')->info('Info message');
```

Method chaining
```php
Notification::container('myContainer')->info('Info message')->error('Error message');
```

If you want to use default container just use ```null``` as container name. Name will be taken from config file.
```php
Notification::container()->info('Info message');
```

### Instant notifications (shown in same request)

Library supports not only flash messages, if you want to show notifications in same request just use
```php
Notification::successInstant('Instant success message');
```

### Custom single message format

Want a custom format for single message? No problem
```php
Notification::success('Success message', 'Custom format :message');
```

### Add multiple messages

If you want to add multiple notifications you can pass notication message as array
```php
Notification::success(array(
    'Message one',
    array('Message two with its format', 'My format: :message')
));
```

Also you can still pass second param (format), to format messages, but you can format individual messages as shown above.

### Accessing first notification from container

You can access and show just first notification in container
```php
{{ Notification::container('myContainer')->first('success')->render() }}
```

Accessing first notification from all types
```php
{{ Notification::container('myContainer')->all()->first()->render() }}
```

### Displaying notifications

To display notifications in a default container you need to add just one line to your view file
```php
{{ Notification::show() }}
```

Also there are some params to display notifications
```php
/**
 * Renders each message by given type (or all) in container.
 *
 * @param null $type - notification type to show (error, success, warning, info), if is null, all notifications will be shown
 * @param null $container - container name (if is null, default container will be used)
 * @param null $format - format for messages, if is null, default formats will be used
 * @return mixed
 */
public function show($type = null, $container = null, $format = null)
{
    return $this->container($container)->show($type, $format);
}
```
