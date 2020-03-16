<?php


class Collivery
{
    const API_BASE_URL = 'http://api.collivery.local/v3/';

    public function requestApi()
    {
        return new  MdsHttpRequest(self::API_BASE_URL);
    }
}

require_once(dirname(__FILE__) . '/mds/MdsHttpRequest.php');