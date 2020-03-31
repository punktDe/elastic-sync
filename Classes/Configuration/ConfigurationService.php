<?php
declare(strict_types=1);

namespace PunktDe\Elastic\Sync\Configuration;

/*
 *  (c) 2020 punkt.de GmbH - Karlsruhe, Germany - http://punkt.de
 *  All rights reserved.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\ConsoleOutput;
use PunktDe\Elastic\Sync\Exception\ConfigurationException;
use PunktDe\Elastic\Sync\Exception\SynchronizationException;

class ConfigurationService
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

    public function __construct()
    {
        $this->consoleOutput = new ConsoleOutput();
    }

    /**
     * @param string $presetName
     * @return PresetConfiguration
     * @throws ConfigurationException
     */
    public function getLocalConfiguration(string $presetName): PresetConfiguration
    {
        $this->consoleOutput->output('Validating local configuration ....');
        $localConfiguration = $this->presetConfigurationFactory->getLocalConfiguration($presetName);

        $this->consoleOutput->outputLine('<success>done</success>');

        return $localConfiguration;
    }

    /**
     * @param string $presetName
     * @return PresetConfiguration
     * @throws SynchronizationException
     */
    public function getRemoteConfiguration(string $presetName): PresetConfiguration
    {
        $this->consoleOutput->output('Fetching and validating remote configuration ....');
        try {
            $remoteConfiguration = $this->presetConfigurationFactory->getRemoteConfiguration($presetName);
        } catch (ConfigurationException $exception) {
            $this->consoleOutput->outputLine(sprintf('<error>Error while validating remote settings:</error> %s', $exception->getMessage()));
            throw new SynchronizationException($exception->getMessage(), 1583963778, $exception);
        }
        $this->consoleOutput->outputLine('<success>done</success>');
        return $remoteConfiguration;
    }
}
