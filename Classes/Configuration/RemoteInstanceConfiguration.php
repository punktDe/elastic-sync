<?php
declare(strict_types=1);

namespace PunktDe\Elastic\Sync\Configuration;

/*
 *  (c) 2019 punkt.de GmbH - Karlsruhe, Germany - http://punkt.de
 *  All rights reserved.
 */

class RemoteInstanceConfiguration
{
    protected string $host = '';

    protected string $user;

    protected int $port = 22;

    protected string $sshOptions;

    /**
     * RemoteInstanceConfiguration constructor.
     * @param array $remoteInstanceConfiguration
     */
    public function __construct(array $remoteInstanceConfiguration)
    {
        $this->user = $remoteInstanceConfiguration['user'] ?? '';
        $this->host = $remoteInstanceConfiguration['host'] ?? 'localhost';
        $this->port = $remoteInstanceConfiguration['port'] ? (int)$remoteInstanceConfiguration['port'] : 22;
        $this->sshOptions = $remoteInstanceConfiguration['sshOptions'] ?? '';
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function getUser(): string
    {
        return $this->user;
    }

    public function getPort(): int
    {
        return $this->port;
    }

    public function getSshOptions(): string
    {
        return $this->sshOptions;
    }
}
