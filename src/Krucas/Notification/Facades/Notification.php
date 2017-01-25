<?php namespace Krucas\Notification\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static Notification info(string $message)
 * @method static Notification success(string $message)
 * @method static Notification error(string $message)
 * @method static Notification warning(string $message)
 * 
 * @method static Notification infoInstant(string $message)
 * @method static Notification successInstant(string $message)
 * @method static Notification errorInstant(string $message)
 * @method static Notification warningInstant(string $message)
 * 
 * @method static Notification showAll()
 * @method static Notification showInfo(string $format)
 * @method static Notification showSuccess(string $format)
 * @method static Notification showError(string $format)
 * @method static Notification showWarning(string $format)
 *
 * @method static Notification clearAll() Clear all messages.
 * @method static Notification clearInfo() Cear all info messages.
 * @method static Notification clearSuccess() Clear all success messages.
 * @method static Notification clearError() Clear all error messages.
 * @method static Notification clearWarning() Clear all warning messages.
 * 
 * @method static Notification container(string $container)
 */
class Notification extends Facade
{
    /**
     * Get the registered component.
     *
     * @return object
     */
    protected static function getFacadeAccessor()
    {
        return 'notification';
    }
}
