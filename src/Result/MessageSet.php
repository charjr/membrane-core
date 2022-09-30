<?php

declare(strict_types=1);

namespace Membrane\Result;

use RuntimeException;

class MessageSet
{
    /** @var array|Message[] */
    public readonly array $messages;

    public function __construct(
        public readonly ?Fieldname $fieldname,
        Message ...$messages
    ) {
        $this->messages = $messages;
    }

    public function merge(MessageSet $messageSet): MessageSet
    {
        if (
            $this->fieldname?->mergable($messageSet->fieldname) === false
            ||
            $messageSet->fieldname?->mergable($this->fieldname) === false
        ) {
            throw new RuntimeException('Unable to merge message sets for different fieldnames');
        }

        return new MessageSet(
            $this->fieldname ?? $messageSet->fieldname,
            ...$this->messages,
            ...$messageSet->messages
        );
    }

    public function isEmpty(): bool
    {
        return !(isset($this->messages) && count($this->messages) > 0);
    }
}
