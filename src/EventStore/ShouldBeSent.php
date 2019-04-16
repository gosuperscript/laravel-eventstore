<?php

namespace Mannum\EventStore;

interface ShouldBeSent
{
    public function setStream();
    public function setMetadata();
    public function setPayload();
}
