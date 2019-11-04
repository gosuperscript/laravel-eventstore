# Laravel EventStore

This package integrates Greg Young's `eventstore` into Laravel's event system. By simply implementing `ShouldBeStored` on your events, they will be sent to eventstore. In the same fashion you can also setup listeners that can respond to events that are received from the eventstore.

Example implementation: https://github.com/digitalrisks/laravel-eventstore-example

## Installation

You can install the package via composer:

```bash
composer require digitalrisks/laravel-eventstore
```

Add the base service provider for the package.

``` php
<?php

namespace App\Providers;

use DigitalRisks\LaravelEventStore\EventStore;
use DigitalRisks\LaravelEventStore\ServiceProvider as EventStoreApplicationServiceProvider;

class EventStoreServiceProvider extends EventStoreApplicationServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        parent::boot();

        // This will set your events to be the following '\\App\Events\\' . $event->getType();.
        EventStore::eventToClass();

        // You can customise this by doing the following
        EventStore::eventToClass(function ($event) {
            return 'App\Events\\' . Str::studly($event->getType());
        });
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        parent::register();
    }
}

```

In your `config/app.php` file, add the following to the `providers` array.

``` php
    /*
    |--------------------------------------------------------------------------
    | Autoloaded Service Providers
    |--------------------------------------------------------------------------
    |
    | The service providers listed here will be automatically loaded on the
    | request to your application. Feel free to add your own services to
    | this array to grant expanded functionality to your applications.
    |
    */

    'providers' => [
        ...
        App\Providers\EventStoreServiceProvider::class,
    ],
```

## Example Event

``` php
use DigitalRisks\LaravelEventStore\Contracts\CouldBeReceived;
use DigitalRisks\LaravelEventStore\Contracts\ShouldBeStored;
use DigitalRisks\LaravelEventStore\Traits\ReceivedFromEventStore;
use DigitalRisks\LaravelEventStore\Traits\SendsToEventStore;

class QuoteStarted implements ShouldBeStored, CouldBeReceived
{
    use SendsToEventStore, ReceivedFromEventStore;

    public $email;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($email = null)
    {
        $this->email = $email;
    }
}
```

## Usage - Sending Events

The package will automatically send events dispatched in Laravel that implement the `ShouldBeStored` interface.

``` php
interface ShouldBeStored
{
    public function getEventStream(): string;

    public function getEventType(): string;

    public function getEventId(): string;

    public function getData(): array;

    public function getMetadata(): array;
}
```

To assist in implementing the interface, the package comes with a `SendsToEventStore` trait which meets the requirements of the interface in a basic fashion: 

* Event Type: the event's class name
* Event ID: A UUID v4 will be generated
* Data: all of the events public properties are automatically serialized
* Metadata: data from all of the methods marked with `@metadata` will be collected and serialized

``` php
use DigitalRisks\LaravelEventStore\Contracts\CouldBeReceived;
use DigitalRisks\LaravelEventStore\Contracts\ShouldBeStored;
use DigitalRisks\LaravelEventStore\Traits\SendsToEventStore;

class AccountCreated implements ShouldBeStored, CouldBeReceived
{
    use SendsToEventStore;
    
    public function getEventStream(): string
    {
        return 'accounts';
    }
}
```

Then raising an event is done in the normal Laravel way:

``` php
event(new AccountCreated('foo@bar.com'));
```

### Metadata

Metadata can help trace events around your system. You can include any of the following traits on your event to attach metadata automatically

* `AddsHerokuMetadata`
* `AddsLaravelMetadata`
* `AddsUserMetaData`

Or you can define your own methods to collect metadata. Any method with the `@metadata` annotation will be called:

``` php
class AccountCreated implements ShouldBeStored
{
    use DigitalRisks\LaravelEventStore\Traits\AddsLaravelMetadata;
    
    /** @metadata */
    public function collectIpMetadata()
    {
        return [
            'ip' => $_SERVER['REMOTE_ADDR'],
        ];
    }
}
```

### Testing

If you would like to test that your events are being fired correctly, you can use the Laravel `Event::mock` method, or the package comes with helpers that interact with an eventstore to confirm they have been stored correctly. 

``` php
class AccountCreatedTest extends TestCase
{
    use DigitalRisks\LaravelEventStore\Tests\Traits\InteractsWithEventStore;

    public function test_it_creates_an_event_when_an_account_is_created()
    {
        // Act.
        $this->json('POST', '/api/accounts', ['email' => 'foo@bar.com']);

        // Assert.
        $this->assertEventStoreEventRaised('AccountCreated', 'accounts', ['email' => 'foo@bar.com']);
    }
}
```

## Usage - Receiving Events

You must first run the worker which will listen for events. 

*None of the options are required. By default it will run the persistance subscription with a timeout of 10 seconds and 1 parallel event at a time.*

``` sh
$ php artisan eventstore:worker {--parallel= : How many events to run in parallel.} {--timeout= : How long the event should time out for.}

$ php artisan eventstore:worker --parallel=10 --timeout=5
```

When an event is received, it will be mapped to the Laravel event and the original `EventRecord` can be accessed via `getEventRecord()`. 

You can react to these events in the normal Laravel fashion. 

``` php
class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        AccountCreated::class => [SendAccountCreatedEmail::class],
    ];
}
```

``` php
class SendAccountCreatedEmail
{
    public function handle(AccountCreated $event)
    {
        Mail::to($event->email)->send('Here is your account');
    }
}
```

If you are listening to the same stream where you are firing events, your events WILL BE fired twice - once by Laravel and once received from the event store. You may choose react synchronously if `$event->getEventRecord()` is false and asynchronously if `$event->getEventRecord()` returns the eventstore record. 

``` php
class SendAccountCreatedEmail
{
    public function handle(AccountCreated $event)
    {
        if (! $event->getEventRecord()) return;

        Mail::to($event->email)->send('Here is your account');
    }
}

class SaveAccountToDatabase
{
    public function handle(AccountCreated $event)
    {
        if ($event->getEventRecord()) return;

        Account::create(['email' => $event->email]);
    }
}
```

### Testing

If you would like to test your listeners, the package comes with several helper methods to mimic events being received from the worker.

``` php
class QuoteStartedTest extends TestCase
{
    use \DigitalRisks\LaravelEventStore\Tests\MakesEventRecords;

    public function test_it_sends_an_email_when_an_account_is_created()
    {
        // Arrange.
        $event = $this->makeEventRecord('AccountCreated', ['email' => 'foo@bar.com');

        // Act.
        event($event->getType(), $event);

        // Assert.
        Mail::assertSentTo('foo@bar.com');
    }
}
```

## Configuration

The defaults are set in `config/eventstore.php`. Copy this file to your own config directory to modify the values:

    php artisan vendor:publish --provider="DigitalRisks\LaravelEventStore\ServiceProvider"

``` php
return [
    'tcp_url' => 'tls://admin:changeit@localhost:1113',
    'http_url' => 'http://admin:changeit@localhost:2113',
    'group' => 'account-email-subscription',
    'volatile_streams' => ['quotes', 'accounts'],
    'subscription_streams' => ['quotes', 'accounts'],
];
```

## Testing

``` bash
composer test
```

## Changelog

Please see [CHANGELOG](releases) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

### Security

If you discover any security related issues, please email pawel.trauth@digitalrisks.co.uk instead of using the issue tracker.

## Credits

- [Pawel Trauth](https://github.com/eithed)
- [Craig Morris](https://github.com/morrislaptop)
- [Kani Robinson](https://github.com/kanirobinson)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
