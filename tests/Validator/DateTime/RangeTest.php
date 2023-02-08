<?php

declare(strict_types=1);

namespace Validator\DateTime;

use DateTime;
use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use Membrane\Validator\DateTime\Range;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Membrane\Validator\DateTime\Range
 * @uses   \Membrane\Result\Result
 * @uses   \Membrane\Result\MessageSet
 * @uses   \Membrane\Result\Message
 */
class RangeTest extends TestCase
{
    public function dataSetsToConvertToString(): array
    {
        return [
            'no minimum or maximum' => [
                null,
                null,
                'will return valid',
            ],
            'minimum' => [
                DateTime::createFromFormat(DATE_ATOM, '1970-01-01T00:00:00Z'),
                null,
                'is after Thu, 01 Jan 1970 00:00:00',
            ],
            'maximum' => [
                null,
                DateTime::createFromFormat(DATE_ATOM, '2023-01-16T18:19:57Z'),
                'is before Mon, 16 Jan 2023 18:19:57',
            ],
            'minimum and maximum' => [
                DateTime::createFromFormat(DATE_ATOM, '1970-01-01T00:00:00Z'),
                DateTime::createFromFormat(DATE_ATOM, '2023-01-16T18:19:57Z'),
                'is after Thu, 01 Jan 1970 00:00:00 and before Mon, 16 Jan 2023 18:19:57',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider dataSetsToConvertToString
     */
    public function toStringTest(?DateTime $min, ?DateTime $max, string $expected): void
    {
        $sut = new Range($min, $max);

        $actual = $sut->__toString();

        self::assertSame($expected, $actual);
    }

    /**
     * @test
     */
    public function noMinAndMaxReturnsValid(): void
    {
        $input = new DateTime('1970-01-01 00:00:00 UTC');
        $expected = Result::valid($input);
        $range = new Range();

        $result = $range->validate($input);

        self::assertEquals($expected, $result);
    }

    public function dataSetsWithDatesEarlierThanMin(): array
    {
        return [
            [new DateTime('1960-01-01 00:00:00 UTC'), new DateTime('1970-01-01 00:00:00 UTC')],
            [new DateTime('2022-09-15 09:19:30 UTC'), new DateTime('2022-09-15 15:30:00 UTC')],
        ];
    }

    /**
     * @test
     * @dataProvider dataSetsWithDatesEarlierThanMin
     */
    public function datesEarlierThanMinReturnInvalid(DateTime $input, DateTime $min): void
    {
        $expectedMessage = new Message('DateTime is expected to be after %s', [$min]);
        $expected = Result::invalid($input, new MessageSet(null, $expectedMessage));
        $range = new Range($min);

        $result = $range->validate($input);

        self::assertEquals($expected, $result);
    }

    public function dataSetsWithDatesLaterThanMax(): array
    {
        return [
            [new DateTime('1980-01-01 00:00:00 UTC'), new DateTime('1970-01-01 00:00:00 UTC')],
            [new DateTime('2022-09-15 09:19:30 UTC'), new DateTime('2022-09-15 00:00:00 UTC')],
        ];
    }

    /**
     * @test
     * @dataProvider dataSetsWithDatesLaterThanMax
     */
    public function datesLaterThanMaxReturnInvalid(DateTime $input, DateTime $max): void
    {
        $expectedMessage = new Message('DateTime is expected to be before %s', [$max]);
        $expected = Result::invalid($input, new MessageSet(null, $expectedMessage));
        $range = new Range(null, $max);

        $result = $range->validate($input);

        self::assertEquals($expected, $result);
    }

    public function dataSetsWithDatesWithinRange(): array
    {
        return [
            [
                new DateTime('1980-01-01 00:00:00 UTC'),
                new DateTime('1970-01-01 00:00:00 UTC'),
                new DateTime('1990-01-01 00:00:00 UTC'),
            ],
            [
                new DateTime('3033-01-21 23:01:01 UTC'),
                new DateTime('3033-01-21 23:01:00 UTC'),
                new DateTime('3033-01-21 23:01:02 UTC'),
            ],

        ];
    }

    /**
     * @test
     * @dataProvider dataSetsWithDatesWithinRange
     */
    public function datesWithinRangeReturnValid(DateTime $input, DateTime $min, DateTime $max): void
    {
        $expected = Result::valid($input);
        $range = new Range($min, $max);

        $result = $range->validate($input);

        self::assertEquals($expected, $result);
    }
}
