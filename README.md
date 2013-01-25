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

### Containers

Containers allows you to set up different containers for different placeholders.

You can pass closure to modify containers, simply use this syntax showed below
```php
Notification::container('myContainer', function($container)
{
    $container->info('Test info message');
    $container->error('Error');
});

Also you can access container like this
```php
Notification::container('myContainer')->info('Info message');
```

More coming this weekend.