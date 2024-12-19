<?php

declare(strict_types=1);

namespace Membrane\Console\Service;

use Atto\CodegenTools\ClassDefinition\PHPClassDefinitionProducer;
use Atto\CodegenTools\CodeGeneration\PHPFilesWriter;
use Membrane\OpenAPIReader\Exception\CannotRead;
use Membrane\OpenAPIReader\Exception\CannotSupport;
use Membrane\OpenAPIReader\Exception\InvalidOpenAPI;
use Membrane\OpenAPIReader\MembraneReader;
use Membrane\OpenAPIReader\OpenAPIVersion;
use Membrane\OpenAPIReader\ValueObject\Valid\{V31};
use Psr\Log\LoggerInterface;

class CacheOpenAPIProcessors
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {
    }

    public function cache(
        string $openAPIFilePath,
        string $cacheDestinationFilePath,
        string $cacheNamespace,
        bool $buildRequests = true,
        bool $buildResponses = true
    ): bool {
        $this->logger->info("Reading OpenAPI from $openAPIFilePath");
        try {
            $openAPI = (new MembraneReader([OpenAPIVersion::Version_3_0, OpenAPIVersion::Version_3_1]))
                ->readFromAbsoluteFilePath($openAPIFilePath);
        } catch (CannotRead | CannotSupport | InvalidOpenAPI $e) {
            $this->logger->error($e->getMessage());
            return false;
        }

        $this->logger->info("Checking for write permission to $cacheDestinationFilePath");
        if (!$this->isDestinationAWriteableDirectory($cacheDestinationFilePath)) {
            return false;
        }

        $yieldsClasses = new YieldsClassDefinitions($this->logger);

        $definitionProducer = new PHPClassDefinitionProducer($yieldsClasses(
            $openAPI,
            $cacheNamespace,
            $buildRequests,
            $buildResponses,
        ));

        $destination = rtrim($cacheDestinationFilePath, '/');
        $classWriter = new PHPFilesWriter($destination, $cacheNamespace);
        $classWriter->writeFiles($definitionProducer);

        return true;
    }

    private function isDestinationAWriteableDirectory(string $destination): bool
    {
        while (!file_exists($destination)) {
            $destination = dirname($destination);
        }

        if (is_dir($destination) && is_writable($destination)) {
            return true;
        }

        $this->logger->error("Cannot write to $destination");
        return false;
    }
<<<<<<< HEAD

    /** @param array<string,string> $existingClassNames */
    private function createSuitableClassName(string $nameToConvert, array $existingClassNames): string
    {
        $pascalCaseName = (new ToPascalCase())->filter($nameToConvert)->value;
        $alphanumericName = (new AlphaNumeric())->filter($pascalCaseName)->value;

        assert(is_string($alphanumericName));
        if (is_numeric($alphanumericName[0])) {
            $alphanumericName = 'm' . $alphanumericName;
        }

        if (in_array($alphanumericName, $existingClassNames)) {
            $i = 1;
            do {
                $postfixedName = sprintf('%s%d', $alphanumericName, $i++);
            } while (in_array($postfixedName, $existingClassNames));

            return $postfixedName;
        }

        return $alphanumericName;
    }

    private function getRequestBuilder(): OpenAPIRequestBuilder
    {
        if (!isset($this->requestBuilder)) {
            $this->requestBuilder = new OpenAPIRequestBuilder();
            return $this->requestBuilder;
        }

        return $this->requestBuilder;
    }

    private function getResponseBuilder(): OpenAPIResponseBuilder
    {
        if (!isset($this->responseBuilder)) {
            $this->responseBuilder = new OpenAPIResponseBuilder();
            return $this->responseBuilder;
        }
        return $this->responseBuilder;
    }

    /**
     * @return array<string, array{
     *              'request'?: Processor,
     *              'response'?: array<string,Processor>
     *          }>
     */
    private function buildProcessors(
        V30\OpenAPI | V31\OpenAPI $openAPI,
        bool $buildRequests,
        bool $buildResponses,
    ): array {
        $processors = [];
        foreach ($openAPI->paths as $pathUrl => $path) {
            $this->logger->info("Building Processors for $pathUrl");
            foreach ($path->getOperations() as $method => $operation) {
                $methodObject = Method::tryFrom(strtolower($method));
                if ($methodObject === null) {
                    $this->logger->warning("$method not supported and will be skipped.");
                    continue;
                }

                if ($buildRequests) {
                    $this->logger->info('Building Request processor');
                    $processors[$operation->operationId]['request'] = $this->getRequestBuilder()->build(
                        new OpenAPIRequest(
                            new PathParameterExtractor($pathUrl),
                            $path,
                            $methodObject,
                        )
                    );
                }

                if ($buildResponses) {
                    $processors[$operation->operationId]['response'] = [];
                    foreach ($operation->responses as $code => $response) {
                        $this->logger->info("Building $code Response Processor");

                        $processors[$operation->operationId]['response'][$code] = $this->getResponseBuilder()->build(
                            new OpenAPIResponse(
                                $operation->operationId,
                                (string)$code,
                                $response,
                            )
                        );
                    }
                }
            }
        }
        return $processors;
    }
=======
>>>>>>> 4331571 (Extract service to generate template Processors)
}
