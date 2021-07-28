<?php

namespace Tiltshift\Algoritmeregister;

class ApiClient
{
    private $_apiUrl;

    public function __construct($apiUrl)
    {
        $this->_apiUrl = $apiUrl;
    }

    public function readToepassingen()
    {
        $rs = json_decode(file_get_contents($this->_apiUrl . "/toepassingen"), true);
        $toepassingen = $rs["_embedded"]["toepassingen"];
        return $toepassingen;
    }

    public function createToepassing($data)
    {
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: application/x-www-form-urlencoded',
                'content' => http_build_query($data)
            ]
        ]);
        return json_decode(file_get_contents($this->_apiUrl . "/toepassingen", false, $context), true);
    }

    public function readToepassing($id)
    {
        return json_decode(file_get_contents($this->_apiUrl . "/toepassingen/{$id}"), true);
    }

    public function updateToepassing($id, $data, $token)
    {
        $context = stream_context_create([
            'http' => [
                'method' => 'PUT',
                'header' => 'Content-Type: application/x-www-form-urlencoded',
                'content' => http_build_query($data)
            ]
        ]);
        return json_decode(file_get_contents($this->_apiUrl . "/toepassingen/{$id}?token={$token}", false, $context), true);
    }
}