<?php

namespace DigitalRisks\LaravelEventStore\Tests\Constraint;

use PHPUnit\Framework\ExpectationFailedException;

class EventNotOnEventStoreConstraint extends EventStoreConstraint
{
    public function evaluate($compare, $description = '', $returnResult = false)
    {
        sleep(1);

        $compareEvent = $compare;
        $compareEvent->eventType = class_exists($compareEvent->class) ? $compareEvent->class::$name : $compareEvent->class;

        $exists = $this->checkStream($compareEvent);

        if ($returnResult) {
            return $exists;
        }

        if ($exists) {
            throw new ExpectationFailedException(
                sprintf(
                    'Event %s%s found on Event Store%s',
                    $compareEvent->eventType,
                    !empty($compareEvent->data) ? sprintf(' with data %s', json_encode($compareEvent->data)) : '',
                    !empty($compareEvent->metaData) ? sprintf(' with metadata %s', json_encode($compareEvent->metaData)) : '',
                    !empty($compareEvent->streamName) ? sprintf(' on stream %s', $compareEvent->streamName) : ''
                )
            );
        }

        return $exists;
    }

    /**
     * Returns a string representation of the constraint.
     *
     * @return string
     */
    public function toString(): string
    {
        return 'Event was not raised on Event Store';
    }
}
