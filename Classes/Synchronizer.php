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
use PunktDe\Elastic\Sync\Exception\ConfigurationException;
use PunktDe\Elastic\Sync\Exception\SynchronizationException;

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
     * @var string
     */
    protected $presetName;

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

    public function __construct()
    {
        $this->consoleOutput = new ConsoleOutput();
    }

    /**
     * @param string $presetName
     * @throws SynchronizationException
     */
    public function sync(string $presetName): void
    {
        $this->presetName = $presetName;
        $this->compileScript();
    }

    /**
     * @throws SynchronizationException
     */
    private function compileScript(): void
    {
        $localConfiguration = $this->configurationService->getLocalConfiguration($this->presetName);
        $remoteConfiguration = $this->configurationService->getRemoteConfiguration($this->presetName);
        $remoteInstanceConfiguration = $this->presetConfigurationFactory->getRemoteInstanceConfiguration($this->presetName);

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
            passthru($script);
        } catch (\Neos\FluidAdaptor\Exception $exception) {
            $this->consoleOutput->output('<error>%s</error>', [$exception->getMessage()]);
        }
    }
}
