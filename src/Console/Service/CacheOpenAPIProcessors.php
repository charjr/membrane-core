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
}
