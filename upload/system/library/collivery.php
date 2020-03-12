<?php


class collivery
{
    const API_BASE_URL = 'http://api.collivery.local/v3/';

    /**
     * resolved to singleton | i need new instance for each call
     *
     * protected $registry;
     *
     * public function __construct($registry)
     * {
     *      $this->registry = $registry;
     *      $this->handle();
     * }
     *
     * protected function handle()
     * {
     *      $this->registry->set('MdsHttpRequest', new MdsHttpRequest(self::API_BASE_URL));
     * }
     */


    public function requestApi()
    {
        return new  MdsHttpRequest(self::API_BASE_URL);
    }



}

require_once(dirname(__FILE__) . '/mds/MdsHttpRequest.php');
