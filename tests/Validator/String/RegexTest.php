<?php

declare(strict_types=1);

namespace Validator\String;

use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use Membrane\Validator\String\Regex;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Membrane\Validator\String\Regex
 * @uses   \Membrane\Result\Result
 * @uses   \Membrane\Result\MessageSet
 * @uses   \Membrane\Result\Message
 */
class RegexTest extends TestCase
{
    /** @test */
    public function toStringTest(): void
    {
        $expected = 'matches the regex: "#^[a-zA-Z]+$#"';
        $sut = new Regex('#^[a-zA-Z]+$#');

        $actual = $sut->__toString();

        self::assertSame($expected, $actual);
    }

    /** @test */
    public function toPHPTest(): void
    {
        $sut = new Regex('/[abc]/i');

        $actual = $sut->__toPHP();

        self::assertEquals($sut, eval('return ' . $actual . ';'));
    }

    public static function dataSetsWithIncorrectTypes(): array
    {
        return [
            [123, 'integer'],
            [1.23, 'double'],
            [[], 'array'],
            [true, 'boolean'],
            [null, 'NULL'],
        ];
    }

    /**
     * @test
     * @dataProvider dataSetsWithIncorrectTypes
     */
    public function incorrectTypesReturnInvalidResults($input, $expectedVars): void
    {
        $regex = new Regex('');
        $expected = Result::invalid(
            $input,
            new MessageSet(
                null,
                new Message('Regex Validator requires a string, %s given', [$expectedVars])
            )
        );

        $result = $regex->validate($input);

        self::assertEquals($expected, $result);
    }

    public static function dataSetsThatPass(): array
    {
        return [
            ['//', ''],
            ['/[abc]/i', 'B'],
            ['/\d{3}/', '123'],
        ];
    }

    /**
     * @test
     * @dataProvider dataSetsThatPass
     */
    public function stringsThatMatchPatternReturnValid(string $pattern, string $input): void
    {
        $regex = new Regex($pattern);
        $expected = Result::valid($input);

        $result = $regex->validate($input);

        self::assertEquals($expected, $result);
    }

    public static function dataSetsThatFail(): array
    {
        return [
            ['/abc/', 'ABC'],
            ['/[abc]/', 'd'],
            ['/d{3}/', '12'],
        ];
    }

    /**
     * @test
     * @dataProvider dataSetsThatFail
     */
    public function stringsThatDoNotMatchPatternReturnInvalid(string $pattern, string $input): void
    {
        $regex = new Regex($pattern);
        $expectedMessage = new Message('String does not match the required pattern %s', [$pattern]);
        $expected = Result::invalid($input, new MessageSet(null, $expectedMessage));

        $result = $regex->validate($input);

        self::assertEquals($expected, $result);
    }
}
