<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\Filter\FormatStyle;

use Membrane\Filter;
use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;

final class Matrix implements Filter
{
    private const PRIMITIVE_TYPES = [
        'boolean',
        'string',
        'integer',
        'number',
    ];

    public function __construct(
        private readonly string $type,
        private readonly bool $explode,
    ) {
    }

    public function __toString(): string
    {
        return 'format matrix style value';
    }

    public function __toPHP(): string
    {
        return sprintf(
            'new %s(%s,"%s")',
            self::class,
            $this->type,
            $this->explode ? 'true' : 'false'
        );
    }

    public function filter(mixed $value): Result
    {
        if (!is_string($value)) {
            return Result::invalid(
                $value,
                new MessageSet(
                    null,
                    new Message('Matrix Filter expects string, %s given', [gettype($value)])
                )
            );
        }

        if (in_array($this->type, self::PRIMITIVE_TYPES)) {
            return Result::noResult(preg_replace('#^;.+=#', '', $value, 1));
        }
    }
}
