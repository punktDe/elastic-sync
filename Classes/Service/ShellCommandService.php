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
    protected ConsoleOutput $consoleOutput;

    /**
     * @var int|null
     */
    protected $sshTunnelPid = null;

    public function __construct()
    {
        $this->consoleOutput = new ConsoleOutput();
    }

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

    public function executeLocalShellCommand(string $command, array $arguments = []): ?string
    {
        $shellCommand = vsprintf($command, $arguments);
        return shell_exec($shellCommand);
    }
}
