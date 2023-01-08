<?php

namespace App\Http\Clients;

use GuzzleHttp\Client;
use App\Exceptions\APIClientException;

class ACIClient
{
    protected $client;
    protected $authToken;

    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => 'https://' . env('APIC_IPADDR') .  '/api/',
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
            'verify' => false,
        ]);
        if (!$this->connect()) {
            throw new APIClientException('Unable to connect to APIC');
        }
    }
    protected function connect()
    {
        try {
            $response = $this->client->post('aaaLogin.json', [
                'json' => [
                    'aaaUser' => [
                        'attributes' => [
                            'name' => env('APIC_USERNAME'),
                            'pwd' => env('APIC_PASSWORD'),
                        ],
                    ],
                ],
            ]);
            if ($response->getStatusCode() == 200) {
                $data = json_decode($response->getBody());
                $this->authToken = $data->imdata[0]->aaaLogin->attributes->token;
                return true;
            } else {
                return false;
            }
        } catch (\Exception $e) {
            return false;
        }
    }
    public function getApicVersion()
    {
        $response = $this->client->get('class/topSystem.json', [
            'headers' => [
                'Cookie' => 'APIC-Cookie=' . $this->authToken,
            ],
        ]);
        $data = json_decode($response->getBody());
        return $data->imdata[0]->topSystem->attributes->version;
    }

    public function createTenant($name)
    {
        $data = [
            'fvTenant' => [
                'attributes' => [
                    'name' => $name,
                ],
            ],
        ];
        $response = $this->client->post('node/mo/uni.json', [
            'headers' => [
                'Cookie' => 'APIC-Cookie=' . $this->authToken,
            ],
            'json' => $data,
        ]);
        return $response->getStatusCode() == 201;
    }
}
