<?php
declare(strict_types=1);

namespace PunktDe\Elastic\Sync\Configuration;

/*
 *  (c) 2019 punkt.de GmbH - Karlsruhe, Germany - http://punkt.de
 *  All rights reserved.
 */

class RemoteInstanceConfiguration
{
    /**
     * @var string
     */
    protected $host = '';

    /**
     * @var string
     */
    protected $user;

    /**
     * @var int
     */
    protected $port = 22;

    /**
     * @var string
     */
    protected $sshOptions;

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

    /**
     * @return string
     */
    public function getHost(): string
    {
        return $this->host;
    }

    /**
     * @return string
     */
    public function getUser(): string
    {
        return $this->user;
    }

    /**
     * @return int
     */
    public function getPort(): int
    {
        return $this->port;
    }

    /**
     * @return string
     */
    public function getSshOptions(): string
    {
        return $this->sshOptions;
    }
}
