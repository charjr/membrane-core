<?php

declare(strict_types=1);

namespace OpenAPI\Builder;

use cebe\openapi\Reader;
use Membrane\Builder\Specification;
use Membrane\OpenAPI\Builder\APIBuilder;
use Membrane\OpenAPI\Builder\OpenAPIResponseBuilder;
use Membrane\OpenAPI\Exception\CannotProcessOpenAPI;
use Membrane\OpenAPI\Exception\CannotProcessSpecification;
use Membrane\OpenAPI\ExtractPathParameters\PathMatcher;
use Membrane\OpenAPI\Processor\AllOf;
use Membrane\OpenAPI\Processor\AnyOf;
use Membrane\OpenAPI\Processor\OneOf;
use Membrane\OpenAPI\Specification\APISchema;
use Membrane\OpenAPI\Specification\Arrays;
use Membrane\OpenAPI\Specification\Numeric;
use Membrane\OpenAPI\Specification\Objects;
use Membrane\OpenAPI\Specification\OpenAPIResponse;
use Membrane\OpenAPI\Specification\Response;
use Membrane\OpenAPI\Specification\Strings;
use Membrane\OpenAPI\Specification\TrueFalse;
use Membrane\Processor;
use Membrane\Processor\BeforeSet;
use Membrane\Processor\Collection;
use Membrane\Processor\Field;
use Membrane\Processor\FieldSet;
use Membrane\Result\FieldName;
use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use Membrane\Validator\Collection\Contained;
use Membrane\Validator\Collection\Count;
use Membrane\Validator\Collection\Unique;
use Membrane\Validator\FieldSet\RequiredFields;
use Membrane\Validator\Numeric\Maximum;
use Membrane\Validator\Numeric\Minimum;
use Membrane\Validator\Numeric\MultipleOf;
use Membrane\Validator\String\DateString;
use Membrane\Validator\String\Length;
use Membrane\Validator\String\Regex;
use Membrane\Validator\Type\IsArray;
use Membrane\Validator\Type\IsBool;
use Membrane\Validator\Type\IsFloat;
use Membrane\Validator\Type\IsInt;
use Membrane\Validator\Type\IsList;
use Membrane\Validator\Type\IsNull;
use Membrane\Validator\Type\IsNumber;
use Membrane\Validator\Type\IsString;
use Membrane\Validator\Utility\Passes;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(OpenAPIResponseBuilder::class)]
#[CoversClass(CannotProcessSpecification::class)]
#[CoversClass(CannotProcessOpenAPI::class)]
#[CoversClass(APIBuilder::class)]
#[UsesClass(OpenAPIResponse::class)]
#[UsesClass(\Membrane\OpenAPI\Builder\Arrays::class)]
#[UsesClass(\Membrane\OpenAPI\Builder\TrueFalse::class)]
#[UsesClass(\Membrane\OpenAPI\Builder\Numeric::class)]
#[UsesClass(\Membrane\OpenAPI\Builder\Objects::class)]
#[UsesClass(\Membrane\OpenAPI\Builder\Strings::class)]
#[UsesClass(PathMatcher::class)]
#[UsesClass(AllOf::class)]
#[UsesClass(AnyOf::class)]
#[UsesClass(OneOf::class)]
#[UsesClass(APISchema::class)]
#[UsesClass(Arrays::class)]
#[UsesClass(TrueFalse::class)]
#[UsesClass(Numeric::class)]
#[UsesClass(Objects::class)]
#[UsesClass(Strings::class)]
#[UsesClass(Response::class)]
#[UsesClass(BeforeSet::class)]
#[UsesClass(Collection::class)]
#[UsesClass(Field::class)]
#[UsesClass(FieldSet::class)]
#[UsesClass(FieldName::class)]
#[UsesClass(Message::class)]
#[UsesClass(MessageSet::class)]
#[UsesClass(Result::class)]
#[UsesClass(Contained::class)]
#[UsesClass(Count::class)]
#[UsesClass(Unique::class)]
#[UsesClass(RequiredFields::class)]
#[UsesClass(Maximum::class)]
#[UsesClass(Minimum::class)]
#[UsesClass(MultipleOf::class)]
#[UsesClass(DateString::class)]
#[UsesClass(Length::class)]
#[UsesClass(Regex::class)]
#[UsesClass(IsArray::class)]
#[UsesClass(IsInt::class)]
#[UsesClass(IsList::class)]
#[UsesClass(IsString::class)]
class OpenAPIResponseBuilderTest extends TestCase
{
    public const DIR = __DIR__ . '/../../fixtures/OpenAPI/';

    #[Test, TestDox('It throws an exception if you try to use the keyword "not"')]
    public function throwsExceptionIfNotIsFound(): void
    {
        $openApi = Reader::readFromJsonFile(self::DIR . 'noReferences.json');
        $operation = $openApi->paths->getPath('/responsepath')->get;
        $sut = new OpenAPIResponseBuilder();
        $response = new OpenAPIResponse($operation->operationId, '360', $operation->responses->getResponse('360'));

        self::expectExceptionObject(CannotProcessOpenAPI::unsupportedKeyword('not'));

        $sut->build($response);
    }

    #[Test, TestDox('It supports the Response Specification')]
    public function supportsResponseSpecification(): void
    {
        $specification = self::createStub(OpenAPIResponse::class);
        $sut = new OpenAPIResponseBuilder();

        self::assertTrue($sut->supports($specification));
    }

    #[Test, TestDox('It does not support any Specifications that are not Response')]
    public function doesNotSupportSpecificationsThatAreNotResponse(): void
    {
        $specification = self::createStub(\Membrane\Builder\Specification::class);
        $sut = new OpenAPIResponseBuilder();

        self::assertFalse($sut->supports($specification));
    }

    public static function dataSetsforBuilds(): array
    {
        $noReferences = Reader::readFromJsonFile(self::DIR . 'noReferences.json');
        $petstore = Reader::readFromYamlFile(self::DIR . 'docs/petstore.yaml');

        return [
            'no properties' => [
                new OpenAPIResponse(
                    $noReferences->paths->getPath('/path')->get->operationId,
                    '200',
                    $noReferences->paths->getPath('/path')->get->responses->getResponse('200')
                ),
                new Field('', new Passes()),
            ],
            'int' => [
                new OpenAPIResponse(
                    $noReferences->paths->getPath('/responsepath')->get->operationId,
                    '200',
                    $noReferences->paths->getPath('/responsepath')->get->responses->getResponse('200')
                ),
                new Field('', new IsInt()),
            ],
            'nullable int' => [
                new OpenAPIResponse(
                    $noReferences->paths->getPath('/responsepath')->get->operationId,
                    '201',
                    $noReferences->paths->getPath('/responsepath')->get->responses->getResponse('201')
                ),
                new AnyOf('', new Field('', new IsNull()), new Field('', new IsInt())),
            ],
            'int, inclusive min' => [
                new OpenAPIResponse(
                    $noReferences->paths->getPath('/responsepath')->get->operationId,
                    '202',
                    $noReferences->paths->getPath('/responsepath')->get->responses->getResponse('202')
                ),
                new Field('', new IsInt(), new Minimum(0)),
            ],
            'int, exclusive min' => [
                new OpenAPIResponse(
                    $noReferences->paths->getPath('/responsepath')->get->operationId,
                    '203',
                    $noReferences->paths->getPath('/responsepath')->get->responses->getResponse('203')
                ),
                new Field('', new IsInt(), new Minimum(0, true)),
            ],
            'int, inclusive max' => [
                new OpenAPIResponse(
                    $noReferences->paths->getPath('/responsepath')->get->operationId,
                    '204',
                    $noReferences->paths->getPath('/responsepath')->get->responses->getResponse('204')
                ),
                new Field('', new IsInt(), new Maximum(100)),
            ],
            'int, exclusive max' => [
                new OpenAPIResponse(
                    $noReferences->paths->getPath('/responsepath')->get->operationId,
                    '205',
                    $noReferences->paths->getPath('/responsepath')->get->responses->getResponse('205')
                ),
                new Field('', new IsInt(), new Maximum(100, true)),
            ],
            'int, multipleOf' => [
                new OpenAPIResponse(
                    $noReferences->paths->getPath('/responsepath')->get->operationId,
                    '206',
                    $noReferences->paths->getPath('/responsepath')->get->responses->getResponse('206')
                ),
                new Field('', new IsInt(), new MultipleOf(3)),
            ],
            'int, enum' => [
                new OpenAPIResponse(
                    $noReferences->paths->getPath('/responsepath')->get->operationId,
                    '207',
                    $noReferences->paths->getPath('/responsepath')->get->responses->getResponse('207')
                ),
                new Field('', new IsInt(), new Contained([1, 2, 3])),
            ],
            'nullable int, enum, exclusive min, inclusive max, multipleOf' => [
                new OpenAPIResponse(
                    $noReferences->paths->getPath('/responsepath')->get->operationId,
                    '209',
                    $noReferences->paths->getPath('/responsepath')->get->responses->getResponse('209')
                ),
                new AnyOf(
                    '',
                    new Field('', new IsNull()),
                    new Field(
                        '',
                        new IsInt(),
                        new Contained([1, 2, 3]),
                        new Maximum(100),
                        new Minimum(0, true),
                        new MultipleOf(3)

                    )
                ),
            ],
            'number' => [
                new OpenAPIResponse(
                    $noReferences->paths->getPath('/responsepath')->get->operationId,
                    '210',
                    $noReferences->paths->getPath('/responsepath')->get->responses->getResponse('210')
                ),
                new Field('', new IsNumber()),
            ],
            'nullable number' => [
                new OpenAPIResponse(
                    $noReferences->paths->getPath('/responsepath')->get->operationId,
                    '211',
                    $noReferences->paths->getPath('/responsepath')->get->responses->getResponse('211')
                ),
                new AnyOf('', new Field('', new IsNull()), new Field('', new IsNumber())),
            ],
            'number, enum' => [
                new OpenAPIResponse(
                    $noReferences->paths->getPath('/responsepath')->get->operationId,
                    '212',
                    $noReferences->paths->getPath('/responsepath')->get->responses->getResponse('212')
                ),
                new Field('', new IsNumber(), new Contained([1, 2.3, 4])),
            ],
            'number, float format' => [
                new OpenAPIResponse(
                    $noReferences->paths->getPath('/responsepath')->get->operationId,
                    '213',
                    $noReferences->paths->getPath('/responsepath')->get->responses->getResponse('213')
                ),
                new Field('', new IsFloat()),
            ],
            'nullable number, float format' => [
                new OpenAPIResponse(
                    $noReferences->paths->getPath('/responsepath')->get->operationId,
                    '214',
                    $noReferences->paths->getPath('/responsepath')->get->responses->getResponse('214')
                ),
                new AnyOf('', new Field('', new IsNull()), new Field('', new IsFloat())),
            ],
            'number, double format' => [
                new OpenAPIResponse(
                    $noReferences->paths->getPath('/responsepath')->get->operationId,
                    '215',
                    $noReferences->paths->getPath('/responsepath')->get->responses->getResponse('215')
                ),
                new Field('', new IsFloat()),
            ],
            'nullable number, enum, inclusive min, exclusive max, multipleOf' => [
                new OpenAPIResponse(
                    $noReferences->paths->getPath('/responsepath')->get->operationId,
                    '219',
                    $noReferences->paths->getPath('/responsepath')->get->responses->getResponse('219')
                ),
                new AnyOf(
                    '', new Field('', new IsNull()), new Field(
                        '',
                        new IsNumber(),
                        new Contained([1, 2.3, 4]),
                        new Maximum(99.99, true),
                        new Minimum(6.66),
                        new MultipleOf(3.33)

                    )
                ),
            ],
            'string' => [
                new OpenAPIResponse(
                    $noReferences->paths->getPath('/responsepath')->get->operationId,
                    '220',
                    $noReferences->paths->getPath('/responsepath')->get->responses->getResponse('220')
                ),
                new Field('', new IsString()),
            ],
            'nullable string' => [
                new OpenAPIResponse(
                    $noReferences->paths->getPath('/responsepath')->get->operationId,
                    '221',
                    $noReferences->paths->getPath('/responsepath')->get->responses->getResponse('221')
                ),
                new AnyOf('', new Field('', new IsNull()), new Field('', new IsString())),
            ],
            'string, enum' => [
                new OpenAPIResponse(
                    $noReferences->paths->getPath('/responsepath')->get->operationId,
                    '222',
                    $noReferences->paths->getPath('/responsepath')->get->responses->getResponse('222')
                ),
                new Field('', new IsString(), new Contained(['a', 'b', 'c'])),
            ],
            'string, date format' => [
                new OpenAPIResponse(
                    $noReferences->paths->getPath('/responsepath')->get->operationId,
                    '223',
                    $noReferences->paths->getPath('/responsepath')->get->responses->getResponse('223')
                ),
                new Field('', new IsString(), new DateString('Y-m-d')),
            ],
            'string, date-time format' => [
                new OpenAPIResponse(
                    $noReferences->paths->getPath('/responsepath')->get->operationId,
                    '224',
                    $noReferences->paths->getPath('/responsepath')->get->responses->getResponse('224')
                ),
                new Field('', new IsString(), new DateString(DATE_ATOM)),
            ],
            'string, minLength' => [
                new OpenAPIResponse(
                    $noReferences->paths->getPath('/responsepath')->get->operationId,
                    '225',
                    $noReferences->paths->getPath('/responsepath')->get->responses->getResponse('225')
                ),
                new Field('', new IsString(), new Length(5)),
            ],
            'string, maxLength' => [
                new OpenAPIResponse(
                    $noReferences->paths->getPath('/responsepath')->get->operationId,
                    '226',
                    $noReferences->paths->getPath('/responsepath')->get->responses->getResponse('226')
                ),
                new Field('', new IsString(), new Length(0, 10)),
            ],
            'string, pattern' => [
                new OpenAPIResponse(
                    $noReferences->paths->getPath('/responsepath')->get->operationId,
                    '227',
                    $noReferences->paths->getPath('/responsepath')->get->responses->getResponse('227')
                ),
                new Field('', new IsString(), new Regex('#[A-Za-z]+#u')),
            ],
            'nullable string, enum, minLength, maxLength, pattern' => [
                new OpenAPIResponse(
                    $noReferences->paths->getPath('/responsepath')->get->operationId,
                    '229',
                    $noReferences->paths->getPath('/responsepath')->get->responses->getResponse('229')
                ),
                new AnyOf(
                    '',
                    new Field('', new IsNull()),
                    new Field(
                        '',
                        new IsString(),
                        new Contained(['a', 'b', 'c']),
                        new Length(5, 10),
                        new Regex('#[A-Za-z]+#u')
                    )
                ),
            ],
            'bool' => [
                new OpenAPIResponse(
                    $noReferences->paths->getPath('/responsepath')->get->operationId,
                    '230',
                    $noReferences->paths->getPath('/responsepath')->get->responses->getResponse('230')
                ),
                new Field('', new IsBool()),
            ],
            'nullable bool' => [
                new OpenAPIResponse(
                    $noReferences->paths->getPath('/responsepath')->get->operationId,
                    '231',
                    $noReferences->paths->getPath('/responsepath')->get->responses->getResponse('231')
                ),
                new AnyOf('', new Field('', new IsNull()), new Field('', new IsBool())),
            ],
            'bool, enum' => [
                new OpenAPIResponse(
                    $noReferences->paths->getPath('/responsepath')->get->operationId,
                    '232',
                    $noReferences->paths->getPath('/responsepath')->get->responses->getResponse('232')
                ),
                new Field('', new IsBool(), new Contained([true])),
            ],
            'nullable bool, enum' => [
                new OpenAPIResponse(
                    $noReferences->paths->getPath('/responsepath')->get->operationId,
                    '239',
                    $noReferences->paths->getPath('/responsepath')->get->responses->getResponse('239')
                ),
                new AnyOf(
                    '',
                    new Field('', new IsNull()),
                    new Field('', new IsBool(), new Contained([true, null]))
                ),
            ],
            'array of ints' => [
                new OpenAPIResponse(
                    $noReferences->paths->getPath('/responsepath')->get->operationId,
                    '240',
                    $noReferences->paths->getPath('/responsepath')->get->responses->getResponse('240')
                ),
                new Collection('', new BeforeSet(new IsList()), new Field('', new IsInt())),
            ],
            'array of strings, enum' => [
                new OpenAPIResponse(
                    $noReferences->paths->getPath('/responsepath')->get->operationId,
                    '241',
                    $noReferences->paths->getPath('/responsepath')->get->responses->getResponse('241')
                ),
                new Collection(
                    '',
                    new BeforeSet(new IsList(), new Contained([['a', 'b', 'c'], ['d', 'e', 'f']])),
                    new Field('', new IsString())
                ),
            ],
            'nullable array of strings' => [
                new OpenAPIResponse(
                    $noReferences->paths->getPath('/responsepath')->get->operationId,
                    '242',
                    $noReferences->paths->getPath('/responsepath')->get->responses->getResponse('242')
                ),
                new AnyOf(
                    '',
                    new Field('', new IsNull()),
                    new Collection('', new BeforeSet(new IsList()), new Field('', new IsString()))
                ),
            ],
            'array of booleans, minItems' => [
                new OpenAPIResponse(
                    $noReferences->paths->getPath('/responsepath')->get->operationId,
                    '243',
                    $noReferences->paths->getPath('/responsepath')->get->responses->getResponse('243')
                ),
                new Collection('', new BeforeSet(new IsList(), new Count(5)), new Field('', new IsBool())),
            ],
            'array of floats, maxItems' => [
                new OpenAPIResponse(
                    $noReferences->paths->getPath('/responsepath')->get->operationId,
                    '244',
                    $noReferences->paths->getPath('/responsepath')->get->responses->getResponse('244')
                ),
                new Collection('', new BeforeSet(new IsList(), new Count(0, 5)), new Field('', new IsFloat())),
            ],
            'array of numbers, uniqueItems' => [
                new OpenAPIResponse(
                    $noReferences->paths->getPath('/responsepath')->get->operationId,
                    '245',
                    $noReferences->paths->getPath('/responsepath')->get->responses->getResponse('245')
                ),
                new Collection('', new BeforeSet(new IsList(), new Unique()), new Field('', new IsNumber())),
            ],
            'nullable array of nullable numbers, enum, minItems, maxItems, uniqueItems' => [
                new OpenAPIResponse(
                    $noReferences->paths->getPath('/responsepath')->get->operationId,
                    '269',
                    $noReferences->paths->getPath('/responsepath')->get->responses->getResponse('269')
                ),
                new AnyOf(
                    '',
                    new Field('', new IsNull()),
                    new Collection(
                        '',
                        new BeforeSet(
                            new IsList(),
                            new Contained([[1, 2.0, null], [4.0, null, 6]]),
                            new Count(2, 5),
                            new Unique()

                        ),
                        new AnyOf('', new Field('', new IsNull()), new Field('', new IsNumber()))
                    )
                ),
            ],
            'object with (string) name' => [
                new OpenAPIResponse(
                    $noReferences->paths->getPath('/responsepath')->get->operationId,
                    '270',
                    $noReferences->paths->getPath('/responsepath')->get->responses->getResponse('270')
                ),
                new FieldSet(
                    '',
                    new BeforeSet(new IsArray()),
                    new Field('name', new IsString())
                ),
            ],
            'object with (int) id, enum' => [
                new OpenAPIResponse(
                    $noReferences->paths->getPath('/responsepath')->get->operationId,
                    '271',
                    $noReferences->paths->getPath('/responsepath')->get->responses->getResponse('271')
                ),
                new FieldSet(
                    '',
                    new BeforeSet(new IsArray(), new Contained([['id' => 5], ['id' => 10]])),
                    new Field('id', new IsInt())
                ),
            ],
            'nullable object with (float) price' => [
                new OpenAPIResponse(
                    $noReferences->paths->getPath('/responsepath')->get->operationId,
                    '272',
                    $noReferences->paths->getPath('/responsepath')->get->responses->getResponse('272')
                ),
                new AnyOf(
                    '',
                    new Field('', new IsNull()),
                    new FieldSet('', new BeforeSet(new IsArray()), new Field('price', new IsFloat()))

                ),
            ],
            'object with (string) name, (int) id, (bool) status' => [
                new OpenAPIResponse(
                    $noReferences->paths->getPath('/responsepath')->get->operationId,
                    '273',
                    $noReferences->paths->getPath('/responsepath')->get->responses->getResponse('273')
                ),
                new FieldSet(
                    '',
                    new BeforeSet(new IsArray()),
                    new Field('name', new IsString()),
                    new Field('id', new IsInt()),
                    new Field('status', new IsBool())
                ),
            ],
            'object with (string) name, (int) id, (bool) status, required' => [
                new OpenAPIResponse(
                    $noReferences->paths->getPath('/responsepath')->get->operationId,
                    '274',
                    $noReferences->paths->getPath('/responsepath')->get->responses->getResponse('274')
                ),
                new FieldSet(
                    '',
                    new BeforeSet(new IsArray(), new RequiredFields('name', 'id')),
                    new Field('name', new IsString()),
                    new Field('id', new IsInt()),
                    new Field('status', new IsBool())
                ),
            ],
            'nullable object with (string) name, (int) id, (bool) status, enum, required' => [
                new OpenAPIResponse(
                    $noReferences->paths->getPath('/responsepath')->get->operationId,
                    '299',
                    $noReferences->paths->getPath('/responsepath')->get->responses->getResponse('299')
                ),
                new FieldSet(
                    '',
                    new BeforeSet(
                        new IsArray(),
                        new Contained(
                            [
                                ['name' => 'Ben', 'id' => 5, 'status' => true],
                                ['name' => 'Blink', 'id' => 10, 'status' => true],
                            ]
                        ),
                        new RequiredFields('name', 'id')
                    ),
                    new Field('name', new IsString()),
                    new Field('id', new IsInt()),
                    new Field('status', new IsBool())
                ),
            ],
            'allOf, one object (should act like normal object)' => [
                new OpenAPIResponse(
                    $noReferences->paths->getPath('/responsepath')->get->operationId,
                    '300',
                    $noReferences->paths->getPath('/responsepath')->get->responses->getResponse('300')
                ),
                new FieldSet('', new Field('name', new IsString()), new BeforeSet(new IsArray())),
            ],
            'allOf, two objects, one identical parameter' => [
                new OpenAPIResponse(
                    $noReferences->paths->getPath('/responsepath')->get->operationId,
                    '301',
                    $noReferences->paths->getPath('/responsepath')->get->responses->getResponse('301')
                ),
                new AllOf(
                    '',
                    new FieldSet('', new Field('id', new IsInt()), new BeforeSet(new IsArray())),
                    new FieldSet('', new Field('id', new IsInt()), new BeforeSet(new IsArray()))
                ),
            ],
            'allOf, two objects, one unique parameters' => [
                new OpenAPIResponse(
                    $noReferences->paths->getPath('/responsepath')->get->operationId,
                    '302',
                    $noReferences->paths->getPath('/responsepath')->get->responses->getResponse('302')
                ),
                new AllOf(
                    '',
                    new FieldSet('', new Field('id', new IsInt()), new BeforeSet(new IsArray())),
                    new FieldSet('', new Field('name', new IsString()), new BeforeSet(new IsArray()))
                ),
            ],
            'allOf, two objects, conflicting parameter' => [
                new OpenAPIResponse(
                    $noReferences->paths->getPath('/responsepath')->get->operationId,
                    '303',
                    $noReferences->paths->getPath('/responsepath')->get->responses->getResponse('303')
                ),
                new AllOf(
                    '',
                    new FieldSet('', new Field('id', new IsInt()), new BeforeSet(new IsArray())),
                    new FieldSet('', new Field('id', new IsString()), new BeforeSet(new IsArray()))
                ),
            ],
            'allOf, two objects, unique parameters, one requiredField' => [
                new OpenAPIResponse(
                    $noReferences->paths->getPath('/responsepath')->get->operationId,
                    '304',
                    $noReferences->paths->getPath('/responsepath')->get->responses->getResponse('304')
                ),
                new AllOf(
                    '',
                    new FieldSet('', new Field('id', new IsInt()), new BeforeSet(new IsArray())),
                    new FieldSet(
                        '',
                        new BeforeSet(new IsArray(), new RequiredFields('name')),
                        new Field('name', new IsString())
                    )
                ),
            ],
            'allOf, two objects, unique parameters, two requiredField' => [
                new OpenAPIResponse(
                    $noReferences->paths->getPath('/responsepath')->get->operationId,
                    '305',
                    $noReferences->paths->getPath('/responsepath')->get->responses->getResponse('305')
                ),
                new AllOf(
                    '',
                    new FieldSet(
                        '',
                        new Field('id', new IsInt()),
                        new BeforeSet(new IsArray(), new RequiredFields('id'))
                    ),
                    new FieldSet(
                        '',
                        new Field('name', new IsString()),
                        new BeforeSet(new IsArray(), new RequiredFields('name'))
                    )
                ),
            ],
            'allOf, two objects, unique parameters, two requiredFields requiring the other schemas property' => [
                new OpenAPIResponse(
                    $noReferences->paths->getPath('/responsepath')->get->operationId,
                    '306',
                    $noReferences->paths->getPath('/responsepath')->get->responses->getResponse('306')
                ),
                new AllOf(
                    '',
                    new FieldSet(
                        '',
                        new Field('id', new IsInt()),
                        new BeforeSet(new IsArray(), new RequiredFields('name'))
                    ),
                    new FieldSet(
                        '',
                        new Field('name', new IsString()),
                        new BeforeSet(new IsArray(), new RequiredFields('id'))
                    )
                ),
            ],
            'anyOf, one object (should act like normal object)' => [
                new OpenAPIResponse(
                    $noReferences->paths->getPath('/responsepath')->get->operationId,
                    '320',
                    $noReferences->paths->getPath('/responsepath')->get->responses->getResponse('320')
                ),
                new FieldSet('', new Field('name', new IsString()), new BeforeSet(new IsArray())),
            ],
            'anyOf, two objects, one identical parameter' => [
                new OpenAPIResponse(
                    $noReferences->paths->getPath('/responsepath')->get->operationId,
                    '321',
                    $noReferences->paths->getPath('/responsepath')->get->responses->getResponse('321')
                ),
                new AnyOf(
                    '',
                    new FieldSet('', new Field('id', new IsInt()), new BeforeSet(new IsArray())),
                    new FieldSet('', new Field('id', new IsInt()), new BeforeSet(new IsArray()))
                ),
            ],
            'anyOf, two objects, one unique parameters' => [
                new OpenAPIResponse(
                    $noReferences->paths->getPath('/responsepath')->get->operationId,
                    '322',
                    $noReferences->paths->getPath('/responsepath')->get->responses->getResponse('322')
                ),
                new AnyOf(
                    '',
                    new FieldSet('', new Field('id', new IsInt()), new BeforeSet(new IsArray())),
                    new FieldSet('', new Field('name', new IsString()), new BeforeSet(new IsArray()))
                ),
            ],
            'anyOf, two objects, conflicting parameter' => [
                new OpenAPIResponse(
                    $noReferences->paths->getPath('/responsepath')->get->operationId,
                    '323',
                    $noReferences->paths->getPath('/responsepath')->get->responses->getResponse('323')
                ),
                new AnyOf(
                    '',
                    new FieldSet('', new Field('id', new IsInt()), new BeforeSet(new IsArray())),
                    new FieldSet('', new Field('id', new IsString()), new BeforeSet(new IsArray()))
                ),
            ],
            'anyOf, two objects, unique parameters, one requiredField' => [
                new OpenAPIResponse(
                    $noReferences->paths->getPath('/responsepath')->get->operationId,
                    '324',
                    $noReferences->paths->getPath('/responsepath')->get->responses->getResponse('324')
                ),
                new AnyOf(
                    '',
                    new FieldSet('', new Field('id', new IsInt()), new BeforeSet(new IsArray())),
                    new FieldSet(
                        '',
                        new Field('name', new IsString()),
                        new BeforeSet(new IsArray(), new RequiredFields('name'))
                    )
                ),
            ],
            'anyOf, two objects, unique parameters, two requiredField' => [
                new OpenAPIResponse(
                    $noReferences->paths->getPath('/responsepath')->get->operationId,
                    '325',
                    $noReferences->paths->getPath('/responsepath')->get->responses->getResponse('325')
                ),
                new AnyOf(
                    '',
                    new FieldSet(
                        '',
                        new Field('id', new IsInt()),
                        new BeforeSet(new IsArray(), new RequiredFields('id'))
                    ),
                    new FieldSet(
                        '',
                        new Field('name', new IsString()),
                        new BeforeSet(new IsArray(), new RequiredFields('name'))
                    )
                ),
            ],
            'anyOf, two objects, unique parameters, two requiredFields requiring the other schemas property' => [
                new OpenAPIResponse(
                    $noReferences->paths->getPath('/responsepath')->get->operationId,
                    '326',
                    $noReferences->paths->getPath('/responsepath')->get->responses->getResponse('326')
                ),
                new AnyOf(
                    '',
                    new FieldSet(
                        '',
                        new Field('id', new IsInt()),
                        new BeforeSet(new IsArray(), new RequiredFields('name'))
                    ),
                    new FieldSet(
                        '',
                        new Field('name', new IsString()),
                        new BeforeSet(new IsArray(), new RequiredFields('id'))
                    )
                ),
            ],
            'oneOf, one object (should act like normal object)' => [
                new OpenAPIResponse(
                    $noReferences->paths->getPath('/responsepath')->get->operationId,
                    '340',
                    $noReferences->paths->getPath('/responsepath')->get->responses->getResponse('340')
                ),
                new FieldSet('', new Field('name', new IsString()), new BeforeSet(new IsArray())),
            ],
            'oneOf, two objects, one identical parameter' => [
                new OpenAPIResponse(
                    $noReferences->paths->getPath('/responsepath')->get->operationId,
                    '341',
                    $noReferences->paths->getPath('/responsepath')->get->responses->getResponse('341')
                ),
                new OneOf(
                    '',
                    new FieldSet('', new Field('id', new IsInt()), new BeforeSet(new IsArray())),
                    new FieldSet('', new Field('id', new IsInt()), new BeforeSet(new IsArray()))
                ),
            ],
            'oneOf, two objects, one unique parameters' => [
                new OpenAPIResponse(
                    $noReferences->paths->getPath('/responsepath')->get->operationId,
                    '342',
                    $noReferences->paths->getPath('/responsepath')->get->responses->getResponse('342')
                ),
                new OneOf(
                    '',
                    new FieldSet('', new Field('id', new IsInt()), new BeforeSet(new IsArray())),
                    new FieldSet('', new Field('name', new IsString()), new BeforeSet(new IsArray()))
                ),
            ],
            'oneOf, two objects, conflicting parameter' => [
                new OpenAPIResponse(
                    $noReferences->paths->getPath('/responsepath')->get->operationId,
                    '343',
                    $noReferences->paths->getPath('/responsepath')->get->responses->getResponse('343')
                ),
                new OneOf(
                    '',
                    new FieldSet('', new Field('id', new IsInt()), new BeforeSet(new IsArray())),
                    new FieldSet('', new Field('id', new IsString()), new BeforeSet(new IsArray()))
                ),
            ],
            'oneOf, two objects, unique parameters, one requiredField' => [
                new OpenAPIResponse(
                    $noReferences->paths->getPath('/responsepath')->get->operationId,
                    '344',
                    $noReferences->paths->getPath('/responsepath')->get->responses->getResponse('344')
                ),
                new OneOf(
                    '',
                    new FieldSet('', new Field('id', new IsInt()), new BeforeSet(new IsArray())),
                    new FieldSet(
                        '',
                        new Field('name', new IsString()),
                        new BeforeSet(new IsArray(), new RequiredFields('name'))
                    )
                ),
            ],
            'oneOf, two objects, unique parameters, two requiredField' => [
                new OpenAPIResponse(
                    $noReferences->paths->getPath('/responsepath')->get->operationId,
                    '345',
                    $noReferences->paths->getPath('/responsepath')->get->responses->getResponse('345')
                ),
                new OneOf(
                    '',
                    new FieldSet(
                        '',
                        new Field('id', new IsInt()),
                        new BeforeSet(new IsArray(), new RequiredFields('id'))
                    ),
                    new FieldSet(
                        '',
                        new Field('name', new IsString()),
                        new BeforeSet(new IsArray(), new RequiredFields('name'))
                    )
                ),
            ],
            'oneOf, two objects, unique parameters, two requiredFields requiring the other schemas property' => [
                new OpenAPIResponse(
                    $noReferences->paths->getPath('/responsepath')->get->operationId,
                    '346',
                    $noReferences->paths->getPath('/responsepath')->get->responses->getResponse('346')
                ),
                new OneOf(
                    '',
                    new FieldSet(
                        '',
                        new Field('id', new IsInt()),
                        new BeforeSet(new IsArray(), new RequiredFields('name'))
                    ),
                    new FieldSet(
                        '',
                        new Field('name', new IsString()),
                        new BeforeSet(new IsArray(), new RequiredFields('id'))
                    )
                ),
            ],
            'schema with no specified type' => [
                new OpenAPIResponse(
                    $noReferences->paths->getPath('/responsepath')->get->operationId,
                    '404',
                    $noReferences->paths->getPath('/responsepath')->get->responses->getResponse('404')
                ),
                new Field('', new Passes()),
            ],
            'schema with empty content' => [
                new OpenAPIResponse(
                    $noReferences->paths->getPath('/responsepath')->get->operationId,
                    '405',
                    $noReferences->paths->getPath('/responsepath')->get->responses->getResponse('405')
                ),
                new Field('', new Passes()),
            ],
            'schema with no content' => [
                new OpenAPIResponse(
                    $noReferences->paths->getPath('/responsepath')->get->operationId,
                    '406',
                    $noReferences->paths->getPath('/responsepath')->get->responses->getResponse('406')
                ),
                new Field('', new Passes()),
            ],
            'petstore.yaml: /pets path -> get operation -> 200 response' => [
                new OpenAPIResponse(
                    $petstore->paths->getPath('/pets')->get->operationId,
                    '200',
                    $petstore->paths->getPath('/pets')->get->responses->getResponse('200')
                ),
                new Collection(
                    '',
                    new BeforeSet(new IsList(), new Count(0, 100)),
                    new FieldSet(
                        '',
                        new BeforeSet(new IsArray(), new RequiredFields('id', 'name')),
                        new Field('id', new IsInt()),
                        new Field('name', new IsString()),
                        new Field('tag', new IsString())
                    ),
                ),
            ],
        ];
    }

    #[Test, TestDox('It builds processors that can validate data matches response content')]
    #[DataProvider('dataSetsforBuilds')]
    public function buildsTest(Specification $spec, Processor $expected): void
    {
        $sut = new OpenAPIResponseBuilder();

        $processor = $sut->build($spec);

        self::assertEquals($expected, $processor);
    }

    public static function dataSetsForDocExamples(): array
    {
        $petstore = Reader::readFromYamlFile(self::DIR . 'docs/petstore.yaml');

        $petsGet200Response = new OpenAPIResponse(
            $petstore->paths->getPath('/pets')->get->operationId,
            '200',
            $petstore->paths->getPath('/pets')->get->responses->getResponse('200')
        );

        return [
            'dataSet A' => [
                $petsGet200Response,
                [
                    ['name' => 'Blink', 'id' => 1],
                    ['name' => 'Harley', 'id' => 2],
                ],
                Result::valid(
                    [
                        ['name' => 'Blink', 'id' => 1],
                        ['name' => 'Harley', 'id' => 2],
                    ]
                ),
            ],
            'dataSet B' => [
                $petsGet200Response,
                [
                    ['name' => 'Blink'],
                    ['id' => 2],
                ],
                Result::invalid(
                    [
                        ['name' => 'Blink'],
                        ['id' => 2],
                    ],
                    new MessageSet(new FieldName('', '', '', '0', ''), new Message('%s is a required field', ['id'])),
                    new MessageSet(new FieldName('', '', '', '1', ''), new Message('%s is a required field', ['name'])),
                ),
            ],
            'dataSet C' => [
                $petsGet200Response,
                [
                    'Blink',
                    5,
                ],
                Result::invalid(
                    [
                        'Blink',
                        5,
                    ],
                    new MessageSet(
                        new FieldName('', '', '', '0', ''),
                        new Message('IsArray validator expects array value, %s passed instead', ['string'])
                    ),
                    new MessageSet(
                        new FieldName('', '', '', '1', ''),
                        new Message('IsArray validator expects array value, %s passed instead', ['integer'])
                    ),
                ),
            ],
        ];
    }

    #[DataProvider('dataSetsForDocExamples')]
    #[Test]
    public function docsTest(Specification $spec, array $data, Result $expected): void
    {
        $sut = new OpenAPIResponseBuilder();

        $processor = $sut->build($spec);

        self::assertEquals($expected, $processor->process(new FieldName(''), $data));
    }
}
