# Laravel EventStore

[![Latest Version on Packagist](https://img.shields.io/packagist/v/digitalrisks/laravel-eventstore.svg?style=flat-square)](https://packagist.org/packages/digitalrisks/laravel-eventstore)
[![Build Status](https://img.shields.io/travis/com/digitalrisks/laravel-eventstore/master.svg?style=flat-square)](https://travis-ci.com/digitalrisks/laravel-eventstore)
[![Total Downloads](https://img.shields.io/packagist/dt/digitalrisks/laravel-eventstore.svg?style=flat-square)](https://packagist.org/packages/digitalrisks/laravel-eventstore)

This package integrates Greg Young's `eventstore` into Laravel's event system. By simply implementing `ShouldBeEventStored` on your events, they will be sent to eventstore. In the same fashion you can also setup listeners that can respond to events that are received from the eventstore.

## Installation

You can install the package via composer:

```bash
composer require digitalrisks/laravel-eventstore
```

## Usage - Sending Events

The package will automatically send events dispatched in Laravel that implement the `ShouldBeEventStored` interface.

``` php
interface ShouldBeEventStored
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
class QuoteStarted implements DigitalRisks\LaravelEventStore\ShouldBeEventStored
{
    use DigitalRisks\LaravelEventStore\SendsToEventStore;
}
```

Then raising an event is done in the normal Laravel way:

``` php
event(new QuoteStarted($quote));
```

### Metadata

Metadata can help trace events around your system. You can include any of the following traits on your event to attach metadata automatically

* `AddsHerokuMetadata`
* `AddsLaravelMetadata`

Or you can define your own methods to collect metadata. Any method with the `@metadata` annotation will be called:

``` php
class QuoteStarted implements DigitalRisks\LaravelEventStore\ShouldBeEventStored
{
    use DigitalRisks\LaravelEventStore\Tests\Traits\AddsLaravelMetadata;
    
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
class QuoteStartedTest extends TestCase
{
    use DigitalRisks\LaravelEventStore\Tests\Traits\InteractsWithEventStore;

    public function test_it_creates_an_event_when_a_quote_is_started()
    {
        // Act.
        $this->json('POST', '/api/quote', ['email' => 'quote@start.com']);

        // Assert.
        $this->assertEventStoreEventRaised('quote_started', 'quotes', ['email' => 'quote@start.com']);
    }
}
```

## Usage - Receiving Events

You must first run the worker which will listen for events. 

*None of the options are not required. By default it will run the persistance subscription with a timeout of 10 seconds and 1 parallel event at a time.*

``` sh
$ php artisan eventstore:worker
        {--persist : Run persistent mode.}
        {--volatile : Run volatile mode.}
        {--parallel= : How many events to run in parallel.}
        {--timeout= : How long the event should time out for.}
        
$ php artisan eventstore:worker --persist

$ php artisan eventstore:worker --persist --timeout=10

$ php artisan eventstore:worker --persist --parallel=10

$ php artisan eventstore:worker --persist --parallel=10 --timeout=5

$ php artisan eventstore:worker --volatile

$ php artisan eventstore:worker --volatile --timeout=10

$ php artisan eventstore:worker --persist --volatile

$ php artisan eventstore:worker --persist --volatile --timeout=10

$ php artisan eventstore:worker --persist --volatile --parallel=10

$ php artisan eventstore:worker --persist --volatile --parallel=10 --timeout=5

```

When an event is received, it will be dispatched into Laravel's event system with the `event type` and the `EventRecord` as the payload. 

You can react to these events in the normal Laravel fashion. 

``` php
class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        'quote_started' => [SendQuoteStartedEmail::class],
    ];
}
```

``` php
class SendQuoteStartedEmail
{
    public function handle(\Rxnet\EventStore\Record\EventRecord $event)
    {
        Mail::to($event->getData()['email'])->send('Here is your quote');
    }
}
```

If you would like to map received events to Laravel events and then dispatch them, the event type must match the name of your Laravel event and it must accept the `EventRecord` in the constructor. 

``` php
<?php

class QuoteStarted
{
    public function __construct(\Rxnet\EventStore\Record\EventRecord $event)
    {
    }
}
```

### Testing

If you would like to test your listeners, the package comes with several helper methods to mimic events being received from the worker.

``` php
class QuoteStartedTest extends TestCase
{
    use \DigitalRisks\LaravelEventStore\Tests\MakesEventRecords;

    public function test_it_sends_an_email_when_a_quote_is_started()
    {
        // Arrange.
        $event = $this->makeEventRecord('quote_started', ['email' => 'start@quotes.com');

        // Act.
        event($event->getType(), $event);

        // Assert.
        Mail::assertSentTo('start@quotes.com');
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
    'volatile_streams' => ['quotes', 'accounts'],
    'streams' => ['quotes', 'accounts'],
    'group' => 'quote-email-senderer',
    'namespace' => 'App\Events'
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

- [Pawel Trauth](https://github.com/digitalrisks)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
