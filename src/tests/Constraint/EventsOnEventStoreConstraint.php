<?php

namespace Mannum\LaravelEventStore\Tests\Constraint;

use PHPUnit\Framework\ExpectationFailedException;

class EventsOnEventStoreConstraint extends EventStoreConstraint
{
    public function evaluate($compare, $description = '', $returnResult = false)
    {
        sleep(1);

        if (count($compare->classes) == 0) {
            throw new ExpectationFailedException('Empty events list');
        }

        while (! empty($compare->classes)) {
            $params = array_shift($compare->classes);

            // shifted last event, so all events were in list
            if (empty($params)) {
                return true;
            }

            $compareEvent = (object) [
                'class' => $params[0],
                'streamName' => ! empty($params[1]) ? $params[1] : null,
                'data' => ! empty($params[2]) ? $params[2] : null,
                'metaData' => ! empty($params[3]) ? $params[3] : null,
                'limit' => ! empty($params[4]) ? $params[4] : count($compare->classes) + 1,
            ];

            $compareEvent->eventType = class_exists($compareEvent->class) ? $compareEvent->class::$name : $compareEvent->class;

            if (! $this->checkStream($compareEvent)) {
                if ($returnResult) {
                    return false;
                }

                throw new ExpectationFailedException(
                    sprintf(
                        'Event %s%s not found on Event Store%s',
                        $compareEvent->eventType,
                        ! empty($compareEvent->data) ? sprintf(' with data %s', json_encode($compareEvent->data)) : '',
                        ! empty($compareEvent->metaData) ? sprintf(' with metadata %s', json_encode($compareEvent->metaData)) : '',
                        ! empty($compareEvent->streamName) ? sprintf(' on stream %s', $compareEvent->streamName) : ''
                    )
                );
            }
        }
    }

    /**
     * Returns a string representation of the constraint.
     *
     * @return string
     */
    public function toString(): string
    {
        return 'Events were raised on Event Store in order';
    }
}
