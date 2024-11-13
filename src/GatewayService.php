<?php

namespace App;

use RouterOS\Exceptions\ConnectException;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Contracts\Cache\ItemInterface;

final class GatewayService
{
    private static $FILTER_MAP = ['name' => 'name', 'mac' => 'caller-id', 'ip' => 'address'];
    private $gateways = [];
    private $errors = [];

    /**
     * @param array<int,mixed> $hosts
     */
    public function __construct(private array $hosts, private FilesystemAdapter $cache = new FilesystemAdapter(), private ?array $hostidsFromSearch = [])
    {
        foreach ($hosts as $host) {
            $id = $host['hostid'];
            $hostname = $host['host'];
            $ip = $host['interfaces'][0]['ip'];
            try {
                $client = GatewayFacade::createClient(GatewayFacade::createConfig($ip));
                $this->gateways[] = [
                    'id' => $id,
                    'hostname' => $hostname,
                    'ip' => $ip,
                    'client' => GatewayFacade::connect($client),
                ];
            } catch (ConnectException $e) {
                $this->errors[] = [
                    'hostname' => $hostname,
                    'message' => $e->getMessage(),
                ];
            }
        }
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * @return array<string,array>
     */
    public function findLogsWith(string $query, ?string $mac): array
    {
        $results = [];
        $results['meta']['length'] = 0;
        $results['data'] = [];

        foreach ($this->gateways as $gateway) {
            $hostname = $gateway['hostname'];
            $client = $gateway['client'];
            $logs = $this->cache->get('gateway.logs.'.$hostname, function (ItemInterface $item) use (&$client, &$query, &$mac): string {
                $item->expiresAfter(60);

                $logs = $client->findLogsWith($query, $mac);

                return serialize($logs);
            });

            $logs = unserialize($logs);
            $filtered = array_filter($logs, function ($log) use (&$query) {
                $regex = preg_match("/$query/i", $log);

                return 1 === $regex;
            });
            if ($filtered) {
                $len = count($filtered);
                array_push(
                    $results['data'],
                    [
                        'meta' => [
                            'hostname' => $hostname,
                        ],
                        'data' => $filtered,
                    ]
                );
                $results['meta']['length'] += $len;
            }
        }

        return $results;
    }

    public function getUsers(): ?array
    {
        $results = [];
        $results['meta']['length'] = 0;
        $results['data'] = [];

        foreach ($this->gateways as $gateway) {
            $hostname = $gateway['hostname'];
            $client = $gateway['client'];
            $id = $gateway['id'];

            $users = $this->cache->get('gateway.users.'.$hostname, function (ItemInterface $item) use (&$client): string {
                $item->expiresAfter(60);

                $users = $client->getActivePPPUsers();

                return serialize($users);
            });

            $users = unserialize($users);

            if ($users) {
                $len = count($users);
                array_push(
                    $results['data'],
                    [
                        'meta' => [
                            'hostname' => $hostname,
                            'id' => $id,
                        ],
                        'data' => $users,
                    ]
                );
                $results['meta']['length'] += $len;
            }
        }

        return $results;
    }

    /**
     * @return array<string,array>
     */
    public function getShortUserDataBy(string $filter, string $query): array
    {
        $results = [];
        $results['meta']['length'] = 0;
        $results['data'] = [];

        foreach ($this->gateways as $gateway) {
            $client = $gateway['client'];
            $hostname = $gateway['hostname'];
            $id = $gateway['id'];

            $ip = $gateway['ip'];

            $users = $this->cache->get('gateway.users.'.$hostname, function (ItemInterface $item) use (&$client): string {
                $item->expiresAfter(60);
                $users = $client->getActivePPPUsers();

                return serialize($users);
            });
            $users = unserialize($users);
            $filtered = array_filter($users, function ($user) use (&$filter, &$query) {
                $regex = preg_match("/$query/i", $user[GatewayService::$FILTER_MAP[$filter]]);

                return 1 === $regex;
            });
            if ($filtered) {
                $len = count($filtered);
                array_push(
                    $results['data'],
                    [
                        'meta' => [
                            'hostname' => $hostname,
                            'ip' => $ip,
                            'id' => $id,
                        ],
                        'data' => $filtered,
                    ]
                );
                $results['meta']['length'] += $len;
            }
        }

        return $results;
    }

    public function getFullUserDataByName(string $name): array
    {
        $interfaceOverview = $this->gateways[0]['client']->findPPPoEInterfaceOverview($name);
        if (!$interfaceOverview) {
            throw new \Exception('Usuário inexistente!');
        }
        $queue = $this->gateways[0]['client']->findPPPoEQueue($name);
        $interface = $this->gateways[0]['client']->findPPPoEInterface($name);

        return [
            'user' => $interfaceOverview['user'],
            'caller-id' => $interfaceOverview['caller-id'],
            'interface' => $interfaceOverview['interface'],
            'uptime' => $interfaceOverview['uptime'],
            'local-address' => $interfaceOverview['local-address'],
            'remote-address' => $interfaceOverview['remote-address'],
            'max-limit' => $queue['max-limit'] ?? 'N/A',
            'rx-byte' => $interface['rx-byte'],
            'tx-byte' => $interface['tx-byte'],
            'last-link-up-time' => $interface['last-link-up-time'],
        ];
    }
}
