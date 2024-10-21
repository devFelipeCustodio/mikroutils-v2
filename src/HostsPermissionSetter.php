<?php

namespace App;

use App\Entity\User;
use App\ZabbixAPIClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Throwable;

class HostsPermissionSetter
{
    private array $hosts = [];

    function __construct(ZabbixAPIClient $zabbix, HttpClientInterface $client)
    {
        $zabbix = new ZabbixAPIClient($client);

        foreach ($zabbix->fetchHosts()["result"] as $r) {
            $this->hosts[] = $r["hostid"];
        }

    }

    function all(User $user)
    {
        $user->setAllowedHostIds($this->hosts);
    }

    function update(User $user, array $ids)
    {
        $user->setAllowedHostIds($ids);
    }

}
