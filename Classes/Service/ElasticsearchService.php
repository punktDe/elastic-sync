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
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use PunktDe\Elastic\Sync\Configuration\PresetConfiguration;
use PunktDe\Elastic\Sync\Exception\ElasticsearchException;
use PunktDe\Elastic\Sync\Exception\HttpException;

/**
 * @Flow\Scope("singleton")
 */
class ElasticsearchService
{

    #[Flow\Inject]
    protected ServerRequestFactoryInterface $serverRequestFactory;
    #[Flow\Inject]
    protected UriFactory $uriFactory;

    /**
     * @return int Status code
     * @throws CurlEngineException
     * @throws Exception
     * @throws HttpException
     */
    public function deleteIndex(PresetConfiguration $configuration, string $indexName): int
    {
        $uri = $this->getBaseUri($configuration)->withPath('/' . $indexName);

        $request = $this->createRequest($configuration, 'DELETE', $uri);
        $response = (new CurlEngine())->sendRequest($request);

        if ((int)$response->getStatusCode() !== 200 && (int)$response->getStatusCode() !== 404) {
            throw new HttpException(sprintf('Unable to delete the index %s: %s', $indexName, $response->getBody()->getContents()));
        }

        return (int)$response->getStatusCode();
    }

    /**
     * @throws CurlEngineException
     * @throws Exception
     * @throws ElasticsearchException
     */
    public function getIndices(PresetConfiguration $configuration, string $indexName): array
    {
        $uri = $this->getBaseUri($configuration)
            ->withPath('/_cat/indices/' . $indexName)
            ->withQuery('format=JSON');

        $request = $this->createRequest($configuration, 'GET', $uri);
        $response = (new CurlEngine())->sendRequest($request);

        $result = json_decode($response->getBody()->getContents(), true);
        $this->checkForElasticsearchErrorObject($result);
        return $result;
    }

    /**
     * @throws ElasticsearchException
     */
    public function checkForElasticsearchErrorObject(array $result): void
    {
        if (isset($result['error'])) {
            $reason = current($result['error']['root_cause'])['reason'] ?? 'No Reason given';
            $status = $result['status'] ?? 'Unknown status';

            throw new ElasticsearchException(sprintf('Elasticsearch query failed with status "%s": %s', $status, $reason), 1603000587);
        }
    }

    /**
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
        $request = $this->createRequest($configuration, 'POST', $uri)
            ->withBody(ContentStream::fromContents(json_encode($actions, JSON_THROW_ON_ERROR, 512)));
        $response = (new CurlEngine())->sendRequest($request);

        return $response->getStatusCode();
    }

    private function getBaseUri(PresetConfiguration $configuration): UriInterface
    {
        return $this->uriFactory->createUri('')
            ->withScheme($configuration->getElasticsearchScheme())
            ->withHost($configuration->getElasticsearchHost())
            ->withPort($configuration->getElasticsearchPort());
    }

    /**
     * Creates a request which is authenticated with http basic auth, if credentials are configured
     */
    private function createRequest(PresetConfiguration $configuration, string $method, UriInterface $uri): ServerRequestInterface
    {
        $request = $this->serverRequestFactory->createServerRequest($method, $uri);

        if (!$configuration->hasElasticsearchAuthentication()) {
            return $request;
        }

        return $request->withHeader('Authorization', 'Basic ' . base64_encode($configuration->getElasticsearchUsername() . ':' . $configuration->getElasticsearchPassword()));
    }
}
