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
use Neos\Flow\Http\Exception;
use Neos\Http\Factories\UriFactory;
use Psr\Http\Message\ServerRequestFactoryInterface;
use PunktDe\Elastic\Sync\Configuration\PresetConfiguration;
use PunktDe\Elastic\Sync\Exception\HttpException;

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
        $uri = $this->uriFactory->createUri('')
            ->withScheme($configuration->getElasticsearchScheme())
            ->withHost($configuration->getElasticsearchHost())
            ->withPort($configuration->getElasticsearchPort())
            ->withPath('/' . $indexName);

        $request = $this->serverRequestFactory->createServerRequest('DELETE', $uri);
        $response = (new CurlEngine())->sendRequest($request);

        if ((int)$response->getStatusCode() !== 200 && (int)$response->getStatusCode() !== 404) {
            throw new HttpException(sprintf('Unable to delete the index %s: %s', $indexName, $response->getBody()->getContents()));
        }

        return (int)$response->getStatusCode();
    }
}
