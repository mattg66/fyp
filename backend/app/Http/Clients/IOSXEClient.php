<?php

namespace App\Http\Clients;

use GuzzleHttp\Client;
use App\Exceptions\APIClientException;
use Illuminate\Support\Facades\Log;

class IOSXEClient
{
    protected $client;
    protected $authToken;
    protected $username;
    protected $password;

    public function __construct($ipAddr, $username = null, $password = null)
    {
        if ($username === null && $password === null) {
            $this->username = env('PROJECT_ROUTER_USERNAME');
            $this->password = env('PROJECT_ROUTER_SECRET');
        } else {
            $this->username = $username;
            $this->password = $password;
        }
        $this->client = new Client([
            'base_uri' => 'https://' . $ipAddr .  ':1025/restconf/',
            'headers' => [
                'Content-Type' => 'application/yang-data+json',
                'Accept' => 'application/yang-data+json',
            ],
            'verify' => false,
        ]);
        return true;
    }

    function getHighestUsableIPAddress($networkAddress, $subnetMask)
    {
        // Convert network address and subnet mask to long integers
        $networkAddressLong = ip2long($networkAddress);
        $subnetMaskLong = ip2long($subnetMask);

        // Calculate the broadcast address (last address in the subnet)
        $broadcastAddressLong = $networkAddressLong | (~$subnetMaskLong & 0xFFFFFFFF);

        // Calculate the highest usable IP address (one less than the broadcast address)
        $highestUsableIPAddressLong = $broadcastAddressLong - 1;

        // Convert the highest usable IP address back to dotted decimal notation
        $highestUsableIPAddress = long2ip($highestUsableIPAddressLong);

        return $highestUsableIPAddress;
    }
    public function connectionTest($username = null, $password = null)
    {
        try {
            $response = $this->client->get('data/Cisco-IOS-XE-native:native/hostname', [
                'auth' => [
                    $this->username,
                    $this->password
                ],
                'http_errors' => false
            ]);
            if ($response->getStatusCode() == 200) {
                return true;
            } else {
                return false;
            }
        } catch (\Exception $e) {
            throw new APIClientException($e->getMessage());
        }
    }
    public function getVersion()
    {
        try {
            $response = $this->client->get('data/Cisco-IOS-XE-native:native/version', [
                'auth' => [
                    $this->username,
                    $this->password
                ],
                'http_errors' => false
            ]);
            if ($response->getStatusCode() == 200) {
                return json_decode($response->getBody())->{'Cisco-IOS-XE-native:version'};
            } else {
                return false;
            }
        } catch (\Exception $e) {
            throw new APIClientException($e->getMessage());
        }
    }
    public function getSN()
    {
        try {
            $response = $this->client->get('data/Cisco-IOS-XE-device-hardware-oper:device-hardware-data', [
                'auth' => [
                    $this->username,
                    $this->password
                ],
                'http_errors' => false
            ]);
            if ($response->getStatusCode() == 200) {
                return json_decode($response->getBody())->{'Cisco-IOS-XE-device-hardware-oper:device-hardware-data'}->{'device-hardware'}->{'device-inventory'}[0]->{'serial-number'};
            } else {
                return false;
            }
        } catch (\Exception $e) {
            throw new APIClientException($e->getMessage());
        }
    }
    public function setHostname($hostname)
    {
        try {
            $response = $this->client->patch('data/Cisco-IOS-XE-native:native/hostname', [
                'auth' => [
                    $this->username,
                    $this->password
                ],
                'headers' => [
                    'Content-Type' => 'application/yang-data+json',
                    'Accept' => 'application/yang-data+json',
                ],
                'json' => [
                    'Cisco-IOS-XE-native:hostname' => $hostname
                ]
            ]);
            if ($response->getStatusCode() == 204) {
                return true;
            } else {
                return false;
            }
        } catch (\Exception $e) {
            throw new APIClientException($e->getMessage());
        }
    }

    public function setAddresses($wanIp, $subnetMask, $lanNetwork, $lanMask, $wanGateway)
    {
        try {
            $response = $this->client->patch('data/Cisco-IOS-XE-native:native/interface/GigabitEthernet=2', [
                'auth' => [
                    $this->username,
                    $this->password
                ],
                'headers' => [
                    'Content-Type' => 'application/yang-data+json',
                    'Accept' => 'application/yang-data+json',
                ],
                'json' => [
                    "Cisco-IOS-XE-native:GigabitEthernet" => [
                        "name" => "2",
                        "description" => "#WAN#",
                        "ip" => [
                            "address" => [
                                "primary" => [
                                    "address" => $wanIp,
                                    "mask" => $subnetMask,
                                ]
                            ],
                            "Cisco-IOS-XE-nat:nat" => [
                                "outside" => [
                                    null
                                ]
                            ]
                        ],
                        "mop" => [
                            "enabled" => false,
                            "sysid" => false
                        ],
                        "Cisco-IOS-XE-ethernet:negotiation" => [
                            "auto" => true
                        ]
                    ]
                ]
            ]);
            if ($response->getStatusCode() == 204 && $this->setLanIp($this->getHighestUsableIPAddress($lanNetwork, $lanMask), $lanMask) && $this->setStaticRoute($wanGateway) && $this->ACL($lanNetwork, $lanMask)) {
                return true;
            } else {
                return false;
            }
        } catch (\Exception $e) {
            throw new APIClientException($e->getMessage());
        }
    }

    function setLANIp($lanIp, $subnetMask)
    {
        try {
            $response = $this->client->patch('data/Cisco-IOS-XE-native:native/interface/GigabitEthernet=1', [
                'auth' => [
                    $this->username,
                    $this->password
                ],
                'headers' => [
                    'Content-Type' => 'application/yang-data+json',
                    'Accept' => 'application/yang-data+json',
                ],
                'json' => [
                    "Cisco-IOS-XE-native:GigabitEthernet" => [
                        "name" => "1",
                        "description" => "#PROJECT#",
                        "ip" => [
                            "address" => [
                                "primary" => [
                                    "address" => $lanIp,
                                    "mask" => $subnetMask,
                                ]
                            ],
                            "Cisco-IOS-XE-nat:nat" => [
                                "inside" => [
                                    null
                                ]
                            ]
                        ],
                        "mop" => [
                            "enabled" => false,
                            "sysid" => false
                        ],
                        "Cisco-IOS-XE-ethernet:negotiation" => [
                            "auto" => true
                        ]
                    ]
                ]
            ]);
            if ($response->getStatusCode() == 204) {
                return true;
            } else {
                return false;
            }
        } catch (\Exception $e) {
            throw new APIClientException($e->getMessage());
        }
    }

    public function setStaticRoute($wanGateway)
    {
        try {
            $response = $this->client->patch('data/Cisco-IOS-XE-native:native/ip/route', [
                'auth' => [
                    $this->username,
                    $this->password
                ],
                'headers' => [
                    'Content-Type' => 'application/yang-data+json',
                    'Accept' => 'application/yang-data+json',
                ],
                'json' =>
                [
                    "Cisco-IOS-XE-native:route" => [
                        "ip-route-interface-forwarding-list" => [
                            [
                                "prefix" => "0.0.0.0",
                                "mask" => "0.0.0.0",
                                "fwd-list" => [
                                    [
                                        "fwd" => $wanGateway
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]);
            if ($response->getStatusCode() == 204) {
                return true;
            } else {
                return false;
            }
        } catch (\Exception $e) {
            throw new APIClientException($e->getMessage());
        }
    }
    public function ACL($network, $subnetMask)
    {
        try {
            $wildcardMask = implode(".", array_map(function ($x) {
                return 255 - $x;
            }, explode(".", $subnetMask)));

            $response = $this->client->patch('data/Cisco-IOS-XE-native:native/ip/access-list', [
                'auth' => [
                    $this->username,
                    $this->password
                ],
                'headers' => [
                    'Content-Type' => 'application/yang-data+json',
                    'Accept' => 'application/yang-data+json',
                ],
                'json' => [
                    "Cisco-IOS-XE-native:access-list" => [
                        "Cisco-IOS-XE-acl:extended" => [
                            [
                                "name" => "NATACL",
                                "access-list-seq-rule" => [
                                    [
                                        "sequence" => "10",
                                        "ace-rule" => [
                                            "action" => 'permit',
                                            "protocol" => 'ip',
                                            "ipv4-address" => $network,
                                            "mask" => $wildcardMask,
                                            "dst-any" => [null]
                                        ]
                                    ],
                                ]
                            ]
                        ],
                        "Cisco-IOS-XE-acl:standard" => [
                            [
                                "name" => "VTYACL",
                                "access-list-seq-rule" => [
                                    [
                                        "sequence" => "10",
                                        "permit" => [
                                            "std-ace" => [
                                                "ipv4-prefix" => "172.16.1.0",
                                                "mask" => "0.0.0.255"
                                            ]
                                        ]
                                    ],
                                    [
                                        "sequence" => "20",
                                        "permit" => [
                                            "std-ace" => [
                                                "ipv4-prefix" => "172.16.2.0",
                                                "mask" => "0.0.0.255"
                                            ]
                                        ]
                                    ],
                                    [
                                        "sequence" => "30",
                                        "permit" => [
                                            "std-ace" => [
                                                "ipv4-prefix" => "172.16.3.0",
                                                "mask" => "0.0.0.255"
                                            ]
                                        ]
                                    ],
                                    [
                                        "sequence" => "40",
                                        "permit" => [
                                            "std-ace" => [
                                                "ipv4-prefix" => $network,
                                                "mask" => $wildcardMask
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]);
            if ($response->getStatusCode() == 204) {
                return true;
            } else {
                return false;
            }
        } catch (\Exception $e) {
            throw new APIClientException($e->getMessage());
        }
    }
    public function setSubInterface($ipAddr, $subnetMask, $vlanId, $interfaceId)
    {
        try {
            $response = $this->client->put('data/Cisco-IOS-XE-native:native/interface/GigabitEthernet=' . rawurlencode($interfaceId) . '%2E' . $vlanId, [
                'auth' => [
                    $this->username,
                    $this->password
                ],
                'headers' => [
                    'Content-Type' => 'application/yang-data+json',
                    'Accept' => 'application/yang-data+json',
                ],
                'json' => [
                    "Cisco-IOS-XE-native:GigabitEthernet" => [
                        "name" => $interfaceId . '.' . strval($vlanId),
                        "description" => "#PROJECT#",
                        "encapsulation" => [
                            "dot1Q" => [
                                "vlan-id" => strval($vlanId)
                            ]
                        ],
                        "ip" => [
                            "address" => [
                                "primary" => [
                                    "address" => $ipAddr,
                                    "mask" => $subnetMask
                                ]
                            ]
                        ],
                    ]
                ]
            ]);
            if ($response->getStatusCode() == 201 || $response->getStatusCode() == 204) {
                return true;
            }
            return false;
        } catch (\Exception $e) {
            throw new APIClientException($e->getMessage());
        }
    }
    public function deleteSubIf($vlanId, $interfaceId)
    {
        try {
            $response = $this->client->delete('data/Cisco-IOS-XE-native:native/interface/GigabitEthernet=' . rawurlencode($interfaceId) . '%2E' . $vlanId, [
                'auth' => [
                    $this->username,
                    $this->password
                ],
                'headers' => [
                    'Content-Type' => 'application/yang-data+json',
                    'Accept' => 'application/yang-data+json',
                ],
                'http_errors' => false,
            ]);
            if ($response->getStatusCode() == 204) {
                return true;
            }
            return false;
        } catch (\Exception $e) {
            throw new APIClientException($e->getMessage());
        }
    }
    public function save()
    {
        try {
            $response = $this->client->post('operations/cisco-ia:save-config', [
                'auth' => [
                    $this->username,
                    $this->password
                ],
                'headers' => [
                    'Content-Type' => 'application/yang-data+json',
                    'Accept' => 'application/yang-data+json',
                ]
            ]);
            if ($response->getStatusCode() == 204) {
                return true;
            }
            return false;
        } catch (\Exception $e) {
            throw new APIClientException($e->getMessage());
        }
    }
}
