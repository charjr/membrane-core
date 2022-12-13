<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\Specification;

use cebe\openapi\spec\Schema;
use Exception;

class Objects extends APISchema
{
    // @TODO support minProperties and maxProperties
    /** @var Schema[] */
    public readonly array $properties;
    /** @var string[]|null */
    public readonly ?array $required;

    public function __construct(string $fieldName, Schema $schema)
    {
        if ($schema->type !== 'object') {
            throw new Exception('Objects Specification requires specified type of object');
        }

        $this->properties = array_filter($schema->properties ?? [], fn($p) => $p instanceof Schema);

        $this->required = $schema->required;

        parent::__construct($fieldName, $schema);
    }
}