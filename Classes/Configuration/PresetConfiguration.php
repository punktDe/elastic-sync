<?php
declare(strict_types=1);

namespace PunktDe\Elastic\Sync\Configuration;

/*
 *  (c) 2019 punkt.de GmbH - Karlsruhe, Germany - http://punkt.de
 *  All rights reserved.
 */

use PunktDe\Elastic\Sync\Exception\ConfigurationException;

class PresetConfiguration
{
    /**
     * @var string[]
     */
    protected $remoteInstance;

    /**
     * @var string
     */
    protected $elasticsearchScheme = 'http';

    /**
     * @var string
     */
    protected $elasticsearchHost = 'localhost';

    /**
     * @var string
     */
    protected $elasticsearchPort = 9200;

    /**
     * @var string[]
     */
    protected $indices = [];

    /**
     * @var string
     */
    protected $presetName;

    /**
     * PresetConfiguration constructor.
     * @param array $presetConfiguration
     * @param string $presetName
     * @throws ConfigurationException
     */
    public function __construct(array $presetConfiguration, string $presetName)
    {
        $this->presetName = $presetName;

        $this->remoteInstance = $presetConfiguration['remoteInstance'];
        $this->elasticsearchScheme = $presetConfiguration['elasticsearch']['scheme'] ?? $this->elasticsearchScheme;
        $this->elasticsearchPort = (int)($presetConfiguration['elasticsearch']['port'] ?? $this->elasticsearchPort);
        $this->elasticsearchHost = $presetConfiguration['elasticsearch']['host'] ?? $this->elasticsearchHost;

        if (!isset($presetConfiguration['indices']) || !is_array($presetConfiguration['indices']) || count($presetConfiguration['indices']) === 0) {
            throw new ConfigurationException(sprintf('No %s indices are defined for this preset.', $presetName), 1564437332);
        }
        $this->indices = $presetConfiguration['indices'];
    }

    /**
     * @return string
     */
    public function getElasticsearchScheme(): string
    {
        return $this->elasticsearchScheme;
    }

    /**
     * @return string
     */
    public function getElasticsearchHost(): string
    {
        return $this->elasticsearchHost;
    }

    /**
     * @return string
     */
    public function getElasticsearchPort(): int
    {
        return $this->elasticsearchPort;
    }

    /**
     * @return string[]
     */
    public function getIndices(): array
    {
        return $this->indices;
    }

    /**
     * @return string
     */
    public function getPresetName(): string
    {
        return $this->presetName;
    }

}