<?php

namespace Tiltshift\Algoritmeregister;

class ApiClient
{
    private $_apiUrl;

    public function __construct($apiUrl)
    {
        $this->_apiUrl = $apiUrl;
        $rs = json_decode(file_get_contents($this->_apiUrl), true);
        $this->_csvExportUrl = $rs["_links"]["ar:exporteren"]["href"];
    }

    public function getCsvExportUrl()
    {
        return $this->_csvExportUrl;
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

    public function deleteToepassing($id, $token)
    {
        $context = stream_context_create([
            'http' => [
                'method' => 'DELETE'
            ]
        ]);
        return json_decode(file_get_contents($this->_apiUrl . "/toepassingen/{$id}?token={$token}", false, $context), true);
    }

    public function readEvents($id)
    {
        $rs = json_decode(file_get_contents($this->_apiUrl . "/events/{$id}"), true);
        $events = $rs["_embedded"]["events"];
        return $events;
    }
}