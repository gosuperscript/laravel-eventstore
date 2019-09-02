<?php

namespace DigitalRisks\LaravelEventStore\Traits;

use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use ReflectionClass;
use ReflectionProperty;

trait SendsToEventStore
{
    public function getData(): array {
        $payload = [];

        foreach ((new ReflectionClass($this))->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            $payload[$property->getName()] = $this->formatProperty($property->getValue($this));
        }

        return $payload;
    }

    public function getMetadata(): array {
        return collect((new ReflectionClass($this))->getMethods())
            ->filter(function ($method) {
                return strpos($method->getDocComment(), '@metadata') !== false;
            })
            ->flatMap(function ($method) {
                return $method->invoke($this);
            })
            ->all();
    }

    public function getEventType(): string
    {
        return str_replace(config('eventstore.namespace') . '\\', '', get_class($this));
    }

    public function getEventId(): UuidInterface
    {
        return Uuid::uuid4();
    }

    /**
     * Format the given value for a property.
     *
     * @param  mixed  $value
     * @return mixed
     */
    protected function formatProperty($value)
    {
        if ($value instanceof Arrayable) {
            return $value->toArray();
        }

        return $value;
    }
}
