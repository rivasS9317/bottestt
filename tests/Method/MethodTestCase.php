<?php

declare(strict_types=1);

namespace TgBotApi\BotApiBase\Tests\Method;

use TgBotApi\BotApiBase\ApiClientInterface;
use TgBotApi\BotApiBase\BotApi;
use TgBotApi\BotApiBase\BotApiHelper;

abstract class MethodTestCase extends \PHPUnit\Framework\TestCase
{
    /**
     * @param $methodName
     * @param $request
     * @param array $result
     * @param array $serialisedFields
     *
     * @return BotApiHelper
     */
    protected function getBot($methodName, $request, $result = [], $serialisedFields = []): BotApiHelper
    {
        $stub = $this->getMockBuilder(ApiClientInterface::class)
            ->getMock();

        $stub->expects($this->once())
            ->method('send')
            ->with(
                $methodName,
                $this->callback(function ($query) use ($request, $serialisedFields) {
                    foreach ($serialisedFields as $serializedField) {
                        $query[$serializedField] = \json_decode($query[$serializedField], true);
                    }
                    $this->assertEquals($request, $query);

                    return true;
                })
            )
            ->willReturn((object) (['ok' => true, 'result' => $result]));

        /* @var ApiClientInterface $stub */
        return new BotApiHelper(new BotApi('000000000:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA', $stub));
    }

    /**
     * @param $methodName
     * @param $request
     * @param array $fileMap
     * @param array $serializableFields
     * @param array $result
     *
     * @return BotApiHelper
     */
    protected function getBotWithFiles(
        $methodName,
        $request,
        array $fileMap,
        array $serializableFields = [],
        $result = []
    ): BotApiHelper {
        $requestedData = [];

        $stub = $this->getMockBuilder(ApiClientInterface::class)
            ->getMock();

        $stub->expects($this->once())
            ->method('send')
            ->with(
                $methodName,
                $this->callback(function ($query) use (&$requestedData) {
                    $requestedData = $query;

                    return true;
                }),
                $this->callback(function ($files) use (&$requestedData, $request, $fileMap, $serializableFields) {
                    $request = $this->buildFileTree($files, $request, $fileMap);
                    foreach ($serializableFields as $field) {
                        $this->assertIsString($requestedData[$field]);
                        $requestedData[$field] = \json_decode($requestedData[$field], true);
                    }
                    $this->assertEquals($request, $requestedData);

                    return true;
                })
            )
            ->willReturn((object) (['ok' => true, 'result' => $result]));

        /* @var ApiClientInterface $stub */
        return new BotApiHelper(new BotApi('000000000:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA', $stub));
    }

    /**
     * @param array $files
     * @param array $request
     * @param array $map
     * @param int   $pointer
     *
     * @return array
     */
    private function buildFileTree($files, &$request, $map, &$pointer = 0): array
    {
        foreach ($map as $key => $field) {
            if (\is_array($field)) {
                $request[$key] = $this->buildFileTree($files, $request[$key], $field, $pointer);
            } else {
                $request[$key] = 'attach://' . \array_keys($files)[$pointer];
                ++$pointer;
            }
        }

        return $request;
    }
}
