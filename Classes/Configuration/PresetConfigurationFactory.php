<?php
declare(strict_types=1);

namespace PunktDe\Elastic\Sync\Configuration;

/*
 *  (c) 2019 punkt.de GmbH - Karlsruhe, Germany - http://punkt.de
 *  All rights reserved.
 */

use Neos\Flow\Annotations as Flow;
use PunktDe\Elastic\Sync\Exception\ConfigurationException;
use PunktDe\Elastic\Sync\Service\ShellCommandService;
use Symfony\Component\Yaml\Yaml;

class PresetConfigurationFactory
{
    /**
     * @Flow\InjectConfiguration(path="presets")
     * @var string[][][]
     */
    protected $configuration;

    /**
     * @Flow\Inject
     */
    protected ShellCommandService $shellCommandService;

    public function getRemoteInstanceConfiguration(string $presetName): RemoteInstanceConfiguration
    {
        return new RemoteInstanceConfiguration($this->configuration[$presetName]['remoteInstance'] ?? []);
    }

    /**
     * @throws ConfigurationException
     */
    public function getLocalConfiguration(string $presetName): PresetConfiguration
    {
        if (!isset($this->configuration[$presetName])) {
            throw new ConfigurationException(sprintf('Preset with name "%s" does not exist. Available are "%s"', $presetName, implode(',', array_keys($this->configuration))), 1585646506);
        }
        return new PresetConfiguration($this->configuration[$presetName] ?? [], $presetName);
    }

    /**
     * @throws ConfigurationException
     */
    public function getRemoteConfiguration(string $presetName): PresetConfiguration
    {
        $remoteConfiguration = [];
        $flowContext = $this->configuration[$presetName]['remoteInstance']['flowContext'] ?? 'Production';
        $flowPath = $this->configuration[$presetName]['remoteInstance']['flowPath'] ?? '';
        $flowCommand = sprintf('configuration:show --type=Settings --path=PunktDe.Elastic.Sync.presets.%s', $presetName);

        if ($flowPath === '') {
            throw new ConfigurationException('The flowPath was not defined for preset ' . $presetName, 1566882834);
        }

        $yamlConfiguration = $this->shellCommandService->executeRemoteFlowCommand(
            $this->getRemoteInstanceConfiguration($presetName),
            $flowPath,
            $flowContext,
            $flowCommand
        );

        if ($yamlConfiguration !== '') {
            $remoteConfiguration = Yaml::parse($yamlConfiguration);
        }

        return new PresetConfiguration($remoteConfiguration, $presetName);
    }
}
