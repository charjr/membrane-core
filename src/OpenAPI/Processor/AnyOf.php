<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\Processor;

use Membrane\Exception\InvalidProcessorArguments;
use Membrane\Processor;
use Membrane\Result\FieldName;
use Membrane\Result\Result;

use function count;

class AnyOf implements Processor
{
    /** @var Processor[] */
    public array $fieldSets;

    public function __construct(private readonly string $processes, Processor ...$fieldSets)
    {
        if (count($fieldSets) < 2) {
            throw InvalidProcessorArguments::redundantProcessor(AnyOf::class);
        }
        $this->fieldSets = $fieldSets;
    }

    public function __toString(): string
    {
        return "Any of the following:\n\t" .
            implode(".\n\t", array_map(fn($p) => preg_replace("#\n#m", "\n\t", (string)$p), $this->fieldSets)) . '.';
    }

    public function processes(): string
    {
        return $this->processes;
    }

    public function process(FieldName $parentFieldName, mixed $value): Result
    {
        $results = [];
        $messageSets = [];

        foreach ($this->fieldSets as $fieldSet) {
            $itemResult = $fieldSet->process($parentFieldName, $value);

            if ($itemResult->result === Result::VALID) {
                return $itemResult;
            }

            if ($itemResult->result === Result::INVALID) {
                $messageSets [] = $itemResult->messageSets[0];
            }

            $results [] = $itemResult->result;
        }

        $result = in_array(Result::INVALID, $results) ? Result::INVALID : Result::NO_RESULT;

        return new Result(
            $value,
            $result,
            ...($result === Result::INVALID ? $messageSets : [])
        );
    }
}
