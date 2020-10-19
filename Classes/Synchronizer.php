<?php
declare(strict_types=1);

namespace PunktDe\Elastic\Sync;

/*
 *  (c) 2019 punkt.de GmbH - Karlsruhe, Germany - http://punkt.de
 *  All rights reserved.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\ConsoleOutput;
use Neos\FluidAdaptor\View\StandaloneView;
use PunktDe\Elastic\Sync\Configuration\ConfigurationService;
use PunktDe\Elastic\Sync\Configuration\PresetConfiguration;
use PunktDe\Elastic\Sync\Configuration\PresetConfigurationFactory;
use PunktDe\Elastic\Sync\Configuration\RemoteInstanceConfiguration;
use PunktDe\Elastic\Sync\Exception\ConfigurationException;
use PunktDe\Elastic\Sync\Exception\SynchronizationException;
use PunktDe\Elastic\Sync\Service\ElasticsearchService;
use PunktDe\Elastic\Sync\Service\ShellCommandService;

class Synchronizer
{
    /**
     * @var ConsoleOutput
     */
    protected $consoleOutput;

    /**
     * @Flow\Inject
     * @var PresetConfigurationFactory
     */
    protected $presetConfigurationFactory;

    /**
     * @Flow\InjectConfiguration(path="elasticDumpPath")
     * @var string
     */
    protected $elasticDumpPath;

    /**
     * @Flow\Inject
     * @var ConfigurationService
     */
    protected $configurationService;

    /**
     * @Flow\Inject
     * @var ElasticsearchService
     */
    protected $elasticSeacrhService;

    /**
     * @Flow\Inject
     * @var ShellCommandService
     */
    protected $shellCommandService;

    public function __construct()
    {
        $this->consoleOutput = new ConsoleOutput();
    }

    /**
     * @param string $presetName
     * @throws SynchronizationException|ConfigurationException
     */
    public function sync(string $presetName): void
    {
        $localConfiguration = $this->configurationService->getLocalConfiguration($presetName);
        $remoteConfiguration = $this->configurationService->getRemoteConfiguration($presetName);
        $remoteInstanceConfiguration = $this->presetConfigurationFactory->getRemoteInstanceConfiguration($presetName);

        $sshTunnelPid = $this->shellCommandService->openSshTunnelToRemoteElasticsearchServer($remoteConfiguration, $remoteInstanceConfiguration);

        try {
            $this->compileAndRunCloneScript($remoteConfiguration, $localConfiguration, $remoteInstanceConfiguration);
            $this->createAdditionalAliases($localConfiguration);
        } catch (\Exception $exception) {
            $this->consoleOutput->output('<error>%s</error>', [$exception->getMessage()]);
        } finally {
            $this->shellCommandService->closeSshTunnelToRemoteElasticsearchServer($sshTunnelPid);
        }
    }


    /**
     * @param PresetConfiguration $remoteConfiguration
     * @param PresetConfiguration $localConfiguration
     * @param RemoteInstanceConfiguration $remoteInstanceConfiguration
     * @throws SynchronizationException
     * @throws \Neos\Flow\Http\Client\CurlEngineException
     * @throws \Neos\Flow\Http\Exception
     */
    private function compileAndRunCloneScript(PresetConfiguration $remoteConfiguration, PresetConfiguration $localConfiguration, RemoteInstanceConfiguration $remoteInstanceConfiguration): void
    {
        $tunneledRemoteConfiguration = $remoteConfiguration->withTunneledConnection();

        $indexConfigurations = [];
        foreach ($remoteConfiguration->getIndices() as $key => $index) {
            $indexConfigurations[$key] = $this->checkAndExpandRemoteIndices($tunneledRemoteConfiguration, $index['indexName']);
        }

        $view = new StandaloneView();
        $view->setTemplatePathAndFilename('resource://PunktDe.Elastic.Sync/Private/Template/CopyElastic.sh.template');
        $view->assignMultiple([
            'localConfiguration' => $localConfiguration,
            'remoteConfiguration' => $remoteConfiguration,
            'remoteInstance' => $remoteInstanceConfiguration,
            'indexConfigurations' => $indexConfigurations,
            'elasticDumpPath' => $this->elasticDumpPath,
        ]);

        $script = $view->render();
        passthru($script);
    }

    /**
     * @param PresetConfiguration $localConfiguration
     * @throws \JsonException
     * @throws \Neos\Flow\Http\Client\CurlEngineException
     * @throws \Neos\Flow\Http\Exception
     */
    private function createAdditionalAliases(PresetConfiguration $localConfiguration): void
    {
        $definedAliases = $localConfiguration->getPostCloneConfiguration('createAliases');

        if (empty($definedAliases)) {
            return;
        }

        $this->consoleOutput->outputLine('<b>Creating aliases</b>');

        foreach ($definedAliases as $alias => $index) {
            $this->elasticSeacrhService->addAlias($localConfiguration, $alias, $index);
            $this->consoleOutput->outputLine('%s -> %s', [$alias, $index]);
        }
    }

    /**
     * @param PresetConfiguration $remoteConfiguration
     * @param string $indexTarget
     * @return array
     * @throws SynchronizationException
     * @throws \Neos\Flow\Http\Client\CurlEngineException
     * @throws \Neos\Flow\Http\Exception
     */
    private function checkAndExpandRemoteIndices(PresetConfiguration $remoteConfiguration, string $indexTarget): array
    {
        $indices = $this->elasticSeacrhService->getIndices($remoteConfiguration, $indexTarget);

        if (empty($indices)) {
            throw new SynchronizationException(sprintf('No index was found with the pattern "%s" on the remote server.', $indices), 1602999099);
        }

        return array_column($indices, 'index');
    }
}
