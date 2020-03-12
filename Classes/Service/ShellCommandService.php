<?php
declare(strict_types=1);

namespace PunktDe\Elastic\Sync\Service;

/*
 *  (c) 2019 punkt.de GmbH - Karlsruhe, Germany - http://punkt.de
 *  All rights reserved.
 */

use Neos\Flow\Cli\ConsoleOutput;
use PunktDe\Elastic\Sync\Configuration\RemoteInstanceConfiguration;

class ShellCommandService
{
    /**
     * @var ConsoleOutput
     */
    protected $consoleOutput;

    public function __construct()
    {
        $this->consoleOutput = new ConsoleOutput();
    }

    /**
     * @param RemoteInstanceConfiguration $remoteInstanceConfiguration
     * @param string $flowPath
     * @param string $flowContext
     * @param string $flowCommand
     * @return string|null
     */
    public function executeRemoteFlowCommand(RemoteInstanceConfiguration $remoteInstanceConfiguration, string $flowPath, string $flowContext, string $flowCommand): ?string
    {
        return $this->executeLocalShellCommand(
            'ssh -p %s %s %s@%s "cd %s; FLOW_CONTEXT=%s ./flow %s"',
            [
                $remoteInstanceConfiguration->getPort(),
                $remoteInstanceConfiguration->getSshOptions(),
                $remoteInstanceConfiguration->getUser(),
                $remoteInstanceConfiguration->getHost(),
                $flowPath,
                $flowContext,
                $flowCommand
            ]
        );
    }

    /**
     * @param string $command
     * @param array $arguments
     * @return string|null
     */
    public function executeLocalShellCommand(string $command, array $arguments = []): ?string
    {
        $shellCommand = vsprintf($command, $arguments);
//        $this->consoleOutput->outputLine('> ' . $shellCommand);
        return shell_exec($shellCommand);
    }
}
