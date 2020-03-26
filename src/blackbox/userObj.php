<?php

use Lcobucci\JWT\Token;


class userObj
{
    public $id;
    public $email;
    public $name;
    public $roles;
    public $expires;

    private $token;


    function __construct( Lcobucci\JWT\Token $token){
        $this->id       = $token->getClaim('sub');
        $this->email    = $token->getClaim('email');
        $this->name     = $token->getClaim('preferred_username');
        $this->roles    = $token->getClaim('roles');
        $this->expires  = $token->getClaim('exp');
        $this->token    = $token;
    }

    function __get($propertyName){
        if( property_exists($this, $propertyName) ){
            return $this->$propertyName;
        }
        throw new Exception("No such property");
    }

}
