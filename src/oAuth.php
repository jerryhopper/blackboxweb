<?php


class oAuth
{

    private $loginUrl;
    private $logoutUrl;
    private $tokenEndoint;
    private $userinfoEndpoint;
    private $jwksEndpoint;


    public function getLoginUrl(){
        return $this->loginUrl;
    }
    public function getLogoutUrl(){
        return $this->logoutUrl;
    }
    public function getTokenEndoint(){
        return $this->tokenEndoint;
    }
    public function getUserinfoEndpoint(){
        return $this->userinfoEndpoint;
    }
    public function getJwksEndpoint(){
        return $this->jwksEndpoint;
    }


}
