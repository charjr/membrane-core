<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\Exception;

use cebe\openapi\exceptions\UnresolvableReferenceException;
use Membrane\OpenAPI\Method;
use RuntimeException;

/*
 * This exception occurs if your Open API is readable but cannot be processed.
 * This may occur for one of the following reasons:
 * 1: Your OpenAPI is invalid according to the OpenAPI Specification
 * 2: Your OpenAPI contains features currently unsupported by Membrane
 */

class CannotProcessOpenAPI extends RuntimeException
{
    public const INVALID_OPEN_API = 0;
    public const UNSUPPORTED_MEDIA_TYPES = 1;
    public const UNSUPPORTED_KEYWORD = 2;
    public const REFERENCES_NOT_RESOLVED = 3;
    public const MISSING_OPERATION_ID = 4;

    public static function invalidOpenAPI(string $fileName, string ...$errors): self
    {
        $message = sprintf(
            "%s is invalid OpenAPI due to the following:\n\t- %s",
            $fileName,
            implode("\n\t- ", $errors)
        );
        return new self($message, self::INVALID_OPEN_API);
    }

    /** @param string[] $mediaTypes */
    public static function unsupportedMediaTypes(array $mediaTypes): self
    {
        $supportedContentTypes = [
            'application/json',
        ];

        $message = sprintf(
            "Membrane currently supports:\n\t- %s\nMediaTypes provided:\n\t- %s",
            implode("\n\t-", $supportedContentTypes),
            implode("\n\t- ", $mediaTypes)
        );

        return new self($message, self::UNSUPPORTED_MEDIA_TYPES);
    }

    public static function unsupportedKeyword(string $keyword): self
    {
        $message = sprintf('Membrane does not currently support the keyword "%s"', $keyword);
        return new self($message, self::UNSUPPORTED_KEYWORD);
    }

    public static function unresolvedReference(string $fileName, UnresolvableReferenceException $e): self
    {
        $message = sprintf('Failed to resolve reference in %s', $fileName);
        return new self($message, self::REFERENCES_NOT_RESOLVED, $e);
    }

    public static function missingOperationId(Method $method): self
    {
        $message = sprintf(
            'Membrane requires all operations have an operationId\n' .
            'operationId is missing for the "%s" operation',
            $method->value
        );
        return new self($message, self::MISSING_OPERATION_ID);
    }
}
