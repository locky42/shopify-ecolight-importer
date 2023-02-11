<?php

namespace App\Services;

class ApiConnector
{
    const API_TOKEN_URL_PARAM = 'APIToken';

    protected array $config = [];

    protected string $url;
    protected string $apiToken;

    public function __construct()
    {
        $this->config = config('services.api_connector');
        $this->url = trim($this->config['url'], '/');
        $this->apiToken = $this->config['APIToken'];
    }

    /**
     * @return mixed
     */
    public function getProducts()
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->getSendUrl(),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_HTTPHEADER => array(
                'Content-Length: 0'
            ),
        ));

        $responseJson = curl_exec($curl);

        curl_close($curl);
        $response = json_decode($responseJson);
        return $response->StoneList;
    }

    /**
     * @return string
     */
    protected function getSendUrl(): string
    {
        return $this->url . '?' . self::API_TOKEN_URL_PARAM . '=' . $this->apiToken;
    }
}
