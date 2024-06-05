<?php
declare(strict_types=1);

namespace PunktDe\Elastic\Sync\Configuration;

/*
 *  (c) 2019 punkt.de GmbH - Karlsruhe, Germany - http://punkt.de
 *  All rights reserved.
 */

use PunktDe\Elastic\Sync\Exception\ConfigurationException;

/*
 * Defines a clone preset including the remote elasticearch configuration
 */
class PresetConfiguration
{
    /**
     * @var string[]
     */
    protected $remoteInstance;

    protected string $elasticsearchScheme = 'http';

    protected string $elasticsearchHost = 'localhost';

    protected int $elasticsearchPort = 9200;

    /**
     * @var string[]
     */
    protected $indices = [];

    protected array $postCloneConfiguration = [];

    protected string $presetName = '';

    /**
     * PresetConfiguration constructor.
     * @param array $presetConfiguration
     * @param string $presetName
     * @throws ConfigurationException
     */
    public function __construct(array $presetConfiguration, string $presetName)
    {
        $this->presetName = $presetName;

        if (!isset($presetConfiguration['remoteInstance']) || !is_array($presetConfiguration['remoteInstance'])) {
            throw new ConfigurationException(sprintf('The configuration part "remoteInstance" for preset %s was not found. Please add this configuration.', $presetName), 1585642280);
        }

        $this->remoteInstance = $presetConfiguration['remoteInstance'];
        $this->elasticsearchScheme = $presetConfiguration['elasticsearch']['scheme'] ?? $this->elasticsearchScheme;
        $this->elasticsearchPort = (int)$presetConfiguration['elasticsearch']['port'] ?? $this->elasticsearchPort;
        $this->elasticsearchHost = $presetConfiguration['elasticsearch']['host'] ?? $this->elasticsearchHost;

        if (!isset($presetConfiguration['indices']) || !is_array($presetConfiguration['indices']) || count($presetConfiguration['indices']) === 0) {
            throw new ConfigurationException(sprintf('No %s indices are defined for this preset.', $presetName), 1564437332);
        }

        $this->indices = $presetConfiguration['indices'];
        $this->postCloneConfiguration = $presetConfiguration['postClone'] ?? [];
    }

    public function getElasticsearchScheme(): string
    {
        return $this->elasticsearchScheme;
    }

    public function getElasticsearchHost(): string
    {
        return $this->elasticsearchHost;
    }

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

    public function getPresetName(): string
    {
        return $this->presetName;
    }

    public function getPostCloneConfiguration(string $postCloneStep = ''): array
    {
        if ($postCloneStep === '') {
            return $this->postCloneConfiguration;
        }

        return $this->postCloneConfiguration[$postCloneStep] ?? [];
    }

    public function withTunneledConnection(): self
    {
        $new = clone $this;
        $new->elasticsearchPort = 9210;
        $new->elasticsearchHost = '127.0.0.1';
        return $new;
    }
}
