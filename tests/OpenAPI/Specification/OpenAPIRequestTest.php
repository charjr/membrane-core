<?php

declare(strict_types=1);

namespace Membrane\Tests\OpenAPI\Specification;

use cebe\openapi\Reader;
use cebe\openapi\spec as Cebe;
use Membrane\OpenAPI\ContentType;
use Membrane\OpenAPI\Exception\CannotProcessOpenAPI;
use Membrane\OpenAPI\Exception\CannotProcessSpecification;
use Membrane\OpenAPI\ExtractPathParameters\PathParameterExtractor;
use Membrane\OpenAPI\Specification\OpenAPIRequest;
use Membrane\OpenAPIReader\Method;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(OpenAPIRequest::class)]
#[CoversClass(CannotProcessOpenAPI::class)]
#[CoversClass(CannotProcessSpecification::class)]
#[UsesClass(PathParameterExtractor::class)]
#[UsesClass(ContentType::class)]
class OpenAPIRequestTest extends TestCase
{
    public PathParameterExtractor $pathParameterExtractor;
    public Cebe\OpenApi $openApi;

    protected function setUp(): void
    {
        $this->openApi = Reader::readFromJsonFile(__DIR__ . '/../../fixtures/OpenAPI/docs/petstore-expanded.json');
        $this->pathParameterExtractor = new PathParameterExtractor('/pets');
    }

    #[Test, TestDox('If the method given is not specified on the PathItem then an exception will be thrown')]
    public function throwsExceptionIfMethodNotFound(): void
    {
        self::expectExceptionObject(CannotProcessSpecification::methodNotFound(Method::DELETE->value));

        new OpenAPIRequest($this->pathParameterExtractor, $this->openApi->paths->getPath('/pets'), Method::DELETE);
    }

    #[Test, TestDox('Throws an exception if the request body contains content that is not supported')]
    public function throwsExceptionIfRequestBodyContentContainsUnsupportedMediaTypes(): void
    {
        $pathItem = Reader::readFromJsonFile(__DIR__ . '/../../fixtures/OpenAPI/noReferences.json')
            ->paths
            ->getPath('/path');
        $pathParameterExtractor = new PathParameterExtractor('/path');

        self::expectExceptionObject(
            CannotProcessOpenAPI::unsupportedMediaTypes(array_keys($pathItem->put->requestBody->content))
        );

        new OpenAPIRequest($pathParameterExtractor, $pathItem, Method::PUT);
    }

    #[Test, TestDox('$parameters will contain an array of parameters with their names as keys')]
    public function parameterswillContainRelevantParameters(): void
    {
        $parameters = $this->openApi->paths->getPath('/pets')->get->parameters;
        $expected = array_combine(array_map(fn($p) => $p->name, $parameters), $parameters);

        $sut = new OpenAPIRequest($this->pathParameterExtractor, $this->openApi->paths->getPath('/pets'), Method::GET);
        self::assertEquals($expected, $sut->parameters);
    }

    #[Test, TestDox('$requestBodySchema will be null if request body has no content')]
    public function requestBodySchemaIsNullIfRequestBodyHasNoContent(): void
    {
        $sut = new OpenAPIRequest($this->pathParameterExtractor, $this->openApi->paths->getPath('/pets'), Method::GET);

        self::assertNull($sut->requestBodySchema);
    }

    #[Test, TestDox('$requestBodySchema will contain the request body schema if it exists')]
    public function requestBodySchemaWillContainRelevantRequestBodyContent(): void
    {
        $expected = $this->openApi->paths->getPath('/pets')->post->requestBody->content['application/json']->schema;
        $sut = new OpenAPIRequest($this->pathParameterExtractor, $this->openApi->paths->getPath('/pets'), Method::POST);

        self::assertEquals($expected, $sut->requestBodySchema);
    }

    #[Test, TestDox('$operationId contains operationId for matching Operation Object')]
    public function operationIdContainsRelevantOperationId(): void
    {
        $sut = new OpenAPIRequest($this->pathParameterExtractor, $this->openApi->paths->getPath('/pets'), Method::GET);

        self::assertEquals('findPets', $sut->operationId);
    }
}
