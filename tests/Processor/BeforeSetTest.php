<?php

declare(strict_types=1);

namespace Processor;

use Membrane\Filter;
use Membrane\Processor\BeforeSet;
use Membrane\Result\Fieldname;
use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use Membrane\Validator;
use Membrane\Validator\Utility\Fails;
use Membrane\Validator\Utility\Indifferent;
use Membrane\Validator\Utility\Passes;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Membrane\Processor\BeforeSet
 * @uses   \Membrane\Result\Result
 * @uses   \Membrane\Result\MessageSet
 * @uses   \Membrane\Result\Message
 * @uses   \Membrane\Validator\Utility\Fails
 * @uses   \Membrane\Validator\Utility\Indifferent
 * @uses   \Membrane\Validator\Utility\Passes
 * @uses   \Membrane\Processor\Field
 * @uses   \Membrane\Result\Fieldname
 */
class BeforeSetTest extends TestCase
{
    /**
     * @test
     */
    public function ProcessesMethodReturnsEmptyString(): void
    {
        $expected = '';
        $beforeSet = new BeforeSet();

        $result = $beforeSet->processes();

        self::assertSame($expected, $result);
    }

    /**
     * @test
     */
    public function NoChainReturnsNoResult(): void
    {
        $input = ['a' => 1, 'b' => 2, 'c' => 3];
        $expected = Result::noResult($input);
        $field = new BeforeSet();

        $result = $field->process(new Fieldname('Parent Fieldname'), $input);

        self::assertEquals($expected, $result);
    }

    public function DataSetsForFiltersOrValidators(): array
    {
        $incrementFilter = new class implements Filter {
            public function filter(mixed $value): Result
            {
                foreach (array_keys($value) as $key) {
                    $value[$key]++;
                }

                return Result::noResult($value);
            }
        };

        $evenNumberFilter = new class implements Filter {
            public function filter(mixed $value): Result
            {
                foreach (array_keys($value) as $key) {
                    $value[$key] *= 2;
                }

                return Result::noResult($value);
            }
        };

        $evenValidator = new class implements Validator {
            public function validate(mixed $value): Result
            {
                foreach (array_keys($value) as $key) {
                    if ($value[$key] % 2 !== 0) {
                        return Result::invalid($value, new MessageSet(
                                null,
                                new Message('not a string', []))
                        );
                    }
                }
                return Result::valid($value);
            }
        };

        return [
            [
                ['a' => 1, 'b' => 2, 'c' => 3],
                Result::valid(['a' => 1, 'b' => 2, 'c' => 3]),
                new Passes(),
            ],
            [
                ['a' => 1, 'b' => 2, 'c' => 3],
                Result::invalid(['a' => 1, 'b' => 2, 'c' => 3], new MessageSet(
                    new Fieldname('', 'test field'),
                    new Message('I always fail', [])
                )),
                new Fails(),
            ],
            [
                ['a' => 1, 'b' => 2, 'c' => 3],
                Result::noResult(['a' => 1, 'b' => 2, 'c' => 3]),
                new Indifferent(),
            ],
            [
                ['a' => 1, 'b' => 2, 'c' => 3],
                Result::valid(['a' => 1, 'b' => 2, 'c' => 3]),
                new Passes(),
                new Indifferent(),
                new Indifferent(),
            ],
            [
                ['a' => 1, 'b' => 2, 'c' => 3],
                Result::valid(['a' => 2, 'b' => 4, 'c' => 6]),
                $evenNumberFilter,
                $evenValidator,
            ],
            [
                ['a' => 1, 'b' => 2, 'c' => 3],
                Result::noResult(['a' => 3, 'b' => 4, 'c' => 5]),
                $incrementFilter,
                $incrementFilter,
            ],
            [
                ['a' => 1, 'b' => 2, 'c' => 3],
                Result::invalid(['a' => 2, 'b' => 3, 'c' => 4], new MessageSet(
                    new Fieldname('', 'test field'),
                    new Message('not a string', [])
                )),
                $incrementFilter,
                $evenValidator,
                $incrementFilter,
            ],
        ];
    }

    /**
     * @test
     * @dataProvider DataSetsForFiltersOrValidators
     */
    public function ProcessesCallsFilterOrValidatorMethods(mixed $input, Result $expected, Filter|Validator ...$chain): void
    {
        $beforeSet = new BeforeSet(...$chain);

        $output = $beforeSet->process(new Fieldname('test field'), $input);

        self::assertEquals($expected, $output);
    }
}
