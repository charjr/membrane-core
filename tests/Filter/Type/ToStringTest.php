<?php

declare(strict_types=1);

namespace Filter\Type;

use Membrane\Filter\Type\ToString;
use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Membrane\Filter\Type\ToString
 * @uses   \Membrane\Result\Result
 * @uses   \Membrane\Result\MessageSet
 * @uses   \Membrane\Result\Message
 */
class ToStringTest extends TestCase
{
    /** @test */
    public function toStringTest(): void
    {
        $expected = 'convert to a string';
        $sut = new ToString();

        $actual = $sut->__toString();

        self::assertSame($expected, $actual);
    }

    /** @test */
    public function toPHPTest(): void
    {
        $sut = new ToString();

        $actual = $sut->__toPHP();

        self::assertEquals($sut, eval('return ' . $actual . ';'));
    }

    public static function dataSetsWithAcceptableInputs(): array
    {
        $classWithMethod = new class () {
            public function __toString(): string
            {
                return 'toString method called';
            }
        };
        return [
            ['string', 'string'],
            [123, '123'],
            [1.23, '1.23'],
            [true, '1'],
            [$classWithMethod, 'toString method called'],
            [null, ''],
        ];
    }

    /**
     * @test
     * @dataProvider dataSetsWithAcceptableInputs
     */
    public function acceptableInputsReturnStrings($input, $expectedValue)
    {
        $toString = new ToString();
        $expected = Result::noResult($expectedValue);

        $result = $toString->filter($input);

        self::assertSame($expected->value, $result->value);
        self::assertEquals($expected->result, $result->result);
    }

    public static function dataSetsWithUnacceptableInputs(): array
    {
        $message = 'ToString filter only accepts objects, null or scalar values, %s given';
        $classWithoutMethod = new class () {
        };

        return [
            [
                ['an', 'array'],
                new Message($message, ['array']),
            ],
            [
                ['a' => 'list'],
                new Message($message, ['array']),
            ],
            [
                $classWithoutMethod,
                new Message('ToString Filter only accepts objects with __toString method', []),
            ],
        ];
    }

    /**
     * @test
     * @dataProvider dataSetsWithUnacceptableInputs
     */
    public function unacceptableTypesReturnInvalid($input, $expectedMessage): void
    {
        $toString = new ToString();
        $expected = Result::invalid($input, new MessageSet(null, $expectedMessage));

        $result = $toString->filter($input);

        self::assertEquals($expected, $result);
    }
}
