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

        $this->createAliases($localConfiguration);
        $this->compileAndRunCloneScript($remoteConfiguration, $localConfiguration, $remoteInstanceConfiguration);
    }


    /**
     * @param PresetConfiguration $remoteConfiguration
     * @param PresetConfiguration $localConfiguration
     * @param RemoteInstanceConfiguration $remoteInstanceConfiguration
     */
    private function compileAndRunCloneScript(PresetConfiguration $remoteConfiguration, PresetConfiguration $localConfiguration, RemoteInstanceConfiguration $remoteInstanceConfiguration): void
    {

        $indexConfiguration = [];
        foreach ($remoteConfiguration->getIndices() as $key => $index) {
            $indexConfiguration[$key] = [
                'remote' => $index,
                'local' => $localConfiguration->getIndices()[$key]
            ];
        }

        try {
            $view = new StandaloneView();
            $view->setTemplatePathAndFilename('resource://PunktDe.Elastic.Sync/Private/Template/CopyElastic.sh.template');
            $view->assignMultiple([
                'localConfiguration' => $localConfiguration,
                'remoteConfiguration' => $remoteConfiguration,
                'remoteInstance' => $remoteInstanceConfiguration,
                'indices' => $indexConfiguration,
                'elasticDumpPath' => $this->elasticDumpPath,
            ]);

            $script = $view->render();
                system($script);
        } catch (\Neos\FluidAdaptor\Exception $exception) {
            $this->consoleOutput->output('<error>%s</error>', [$exception->getMessage()]);
        }
    }

    /**
     * @param PresetConfiguration $localConfiguration
     * @throws \JsonException
     * @throws \Neos\Flow\Http\Client\CurlEngineException
     * @throws \Neos\Flow\Http\Exception
     */
    private function createAliases(PresetConfiguration $localConfiguration): void
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
}
