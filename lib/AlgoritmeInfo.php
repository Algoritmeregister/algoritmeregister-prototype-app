<?php

namespace Tiltshift\Algoritmeregister;

class AlgoritmeInfo
{
    private $_indexed;

    public function __construct($metadata)
    {
        $this->_indexed = [];
        foreach ($metadata as $field) {
            $this->_indexed[$field["eigenschap"]] = $field;
        }
        $this->_uuid = json_decode(file_get_contents("https://www.uuidtools.com/api/generate/v1"))[0];
        $this->_token = "";
        $chars = "ABCDEF0123456789";
        while (strlen($this->_token) < 20) {
            $this->_token .= $chars[mt_rand(0, strlen($chars) - 1)];
        }
    }

    public function setNaam($naam)
    {
        $this->_metadata["naam"]["waarde"] = $naam;
    }

    public function getIndexed()
    {
        return $this->_indexed;
    }

    public function getUuid()
    {
        return $this->_uuid;
    }

    public function getToken()
    {
        return $this->_token;
    }

    public function getHash()
    {
        return md5($this->_token);
    }
}