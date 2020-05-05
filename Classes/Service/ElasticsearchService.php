<?php
declare(strict_types=1);

namespace PunktDe\Elastic\Sync\Service;

/*
 *  (c) 2019 punkt.de GmbH - Karlsruhe, Germany - http://punkt.de
 *  All rights reserved.
 */


use Neos\Flow\Annotations as Flow;
use Neos\Flow\Http\Client\CurlEngine;
use Neos\Flow\Http\Client\CurlEngineException;
use Neos\Flow\Http\ContentStream;
use Neos\Flow\Http\Exception;
use Neos\Http\Factories\UriFactory;
use Psr\Http\Message\ServerRequestFactoryInterface;
use PunktDe\Elastic\Sync\Configuration\PresetConfiguration;
use PunktDe\Elastic\Sync\Exception\HttpException;

/**
 * @Flow\Scope("singleton")
 */
class ElasticsearchService
{

    /**
     * @Flow\Inject
     * @var ServerRequestFactoryInterface
     */
    protected $serverRequestFactory;

    /**
     * @Flow\Inject
     * @var UriFactory
     */
    protected $uriFactory;

    /**
     * @param PresetConfiguration $configuration
     * @param string $indexName
     * @return int Status code
     * @throws CurlEngineException
     * @throws Exception
     * @throws HttpException
     */
    public function deleteIndex(PresetConfiguration $configuration, string $indexName): int
    {
        $uri = $this->getBaseUri($configuration)->withPath('/' . $indexName);

        $request = $this->serverRequestFactory->createServerRequest('DELETE', $uri);
        $response = (new CurlEngine())->sendRequest($request);

        if ((int)$response->getStatusCode() !== 200 && (int)$response->getStatusCode() !== 404) {
            throw new HttpException(sprintf('Unable to delete the index %s: %s', $indexName, $response->getBody()->getContents()));
        }

        return (int)$response->getStatusCode();
    }

    /**
     * @param PresetConfiguration $configuration
     * @param string $aliasName
     * @param string $indexName
     * @return int
     * @throws CurlEngineException
     * @throws Exception
     * @throws \JsonException
     */
    public function addAlias(PresetConfiguration $configuration, string $aliasName, string $indexName): int
    {
        $actions = [
            'add' => [
                'index' => $indexName,
                'alias' => $aliasName
            ]
        ];

        $uri = $this->getBaseUri($configuration)->withPath('/_aliases');
        $request = $this->serverRequestFactory->createServerRequest('POST', $uri)
            ->withBody(ContentStream::fromContents(json_encode($actions, JSON_THROW_ON_ERROR, 512)));
        $response = (new CurlEngine())->sendRequest($request);

        return $response->getStatusCode();
    }

    /**
     * @param PresetConfiguration $configuration
     * @return \Psr\Http\Message\UriInterface
     */
    private function getBaseUri(PresetConfiguration $configuration): \Psr\Http\Message\UriInterface
    {
        return $this->uriFactory->createUri('')
            ->withScheme($configuration->getElasticsearchScheme())
            ->withHost($configuration->getElasticsearchHost())
            ->withPort($configuration->getElasticsearchPort());
    }
}
