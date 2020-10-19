<?php
declare(strict_types=1);

namespace PunktDe\Elastic\Sync\Service;

/*
 *  (c) 2019 punkt.de GmbH - Karlsruhe, Germany - http://punkt.de
 *  All rights reserved.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\ConsoleOutput;
use PunktDe\Elastic\Sync\Configuration\PresetConfiguration;
use PunktDe\Elastic\Sync\Configuration\RemoteInstanceConfiguration;
use PunktDe\Elastic\Sync\Exception\SynchronizationException;

/**
 * @Flow\Scope("singleton")
 */
class ShellCommandService
{
    /**
     * @var ConsoleOutput
     */
    protected $consoleOutput;

    /**
     * @var int
     */
    protected $sshTunnelPid = null;

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
     * @param PresetConfiguration $remoteConfiguration
     * @param RemoteInstanceConfiguration $remoteInstanceConfiguration
     * @return int
     */
    public function openSshTunnelToRemoteElasticsearchServer(PresetConfiguration $remoteConfiguration, RemoteInstanceConfiguration $remoteInstanceConfiguration): int
    {
        $result = $this->executeLocalShellCommand(
            '
ssh %s -C -N -L 127.0.0.1:9210:%s:%s %s@%s &
sshpid=$!
echo $sshpid
',
            [
                $remoteInstanceConfiguration->getSshOptions(),
                $remoteConfiguration->getElasticsearchHost(),
                $remoteConfiguration->getElasticsearchPort(),
                $remoteInstanceConfiguration->getUser(),
                $remoteInstanceConfiguration->getHost(),
            ]
        );

        $this->sshTunnelPid = (int)$result;
        return $this->sshTunnelPid;
    }

    /**
     * @param int|null $sshTunnelPid
     * @return string
     * @throws SynchronizationException
     */
    public function closeSshTunnelToRemoteElasticsearchServer(int $sshTunnelPid = null): ?string
    {
        $sshTunnelPid = $sshTunnelPid ?? $this->sshTunnelPid;
        if ($sshTunnelPid === null) {
            throw new SynchronizationException('No open ssh tunnel was found to close', 1602911786);
        }
        return $this->executeLocalShellCommand('kill %s', [$sshTunnelPid]);
    }

    /**
     * @param string $command
     * @param array $arguments
     * @return string|null
     */
    public function executeLocalShellCommand(string $command, array $arguments = []): ?string
    {
        $shellCommand = vsprintf($command, $arguments);
        return shell_exec($shellCommand);
    }
}
