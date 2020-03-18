<?php

use Lcobucci\JWT\Parser;
use Lcobucci\JWT\ValidationData;


class bbAuth {


    var $oauthClientId       = "82252ce6-ad4a-4a7f-8ff3-f7074f1a58dc";


    var $oauthIssuer         = "idp.surfwijzer.nl";

    private $tokenExpires    = "";
    private $tokenOwner      = "";
    private $tokenOwnerEmail = "";
    private $token           = "";

    private $authenticated  = false;

    function __construct (){

    }



    public function oAuthloginUrl(){
        $lurl = "https://idp.surfwijzer.nl/oauth2/authorize?client_id=".$this->oauthClientId."&response_type=code&redirect_uri=https%3A%2F%2Fapi.surfwijzer.nl%2Fblackbox%2Flogin";
        return $lurl;

        //return "https://idp.surfwijzer.nl/oauth2/authorize?client_id=".$this->oauthClientId."&response_type=code&redirect_uri=https%3A%2F%2Fblackbox.surfwijzer.nl%2Fblackbox%2Flogin";
        //return $this->oauthAuthorizeUrl."?response_type=code&scope=email&client_id=".$this->oauthClientId."&state=&redirect_uri=http%3A%2F%2Fpi.hole%2Fadmin%2Findex.php";
    }

    function exchangeCodeForToken($code){

        $curl = new CurlPost( $this->oauthTokenUrl );

        try {
            // execute the request
            $res = $curl([
                'code' => $code,
                'grant_type' => 'authorization_code',
                'redirect_uri'=>'http://pi.hole/admin/index.php',
                'client_id'=>$this->oauthClientId,
                'client_secret'=> $this->oauthClientSecret,
            ]);

        } catch (\RuntimeException $ex) {
            // catch errors
            die(sprintf('Http error %s with code %d', $ex->getMessage(), $ex->getCode()));
        }

        //echo "<pre>";
        $res = json_decode($res);

        if(isset($res->error) ){
            #$res->error_description;
            #$res->error_reason;
            #$res->error;
            throw new \Exception( $res->error." ".$res->error_description);
        }


        // test the token
        $this->validate( $res->access_token );


        #echo "<pre>";
        #print_r( $this->getTokenExpiry() );
        #print_r( $this->getTokenOwner() );
        #print_r( $this->getTokenOwnerEmail() );
        #echo "</pre>";

        #$res = explode(".",$res->access_token);
        //$res = '';
        #$res =  json_decode(base64_decode($res[1]) );
        return $res->access_token;
    }

    public function isAuthenticated(){
        return $this->authenticated;
    }

    function validate( $token ){

        $time = time();
        $token = (new Parser())->parse((string) $token); // Parses from a string
        $token->getHeaders(); // Retrieves the token header
        //print_r($token->getClaims()); // Retrieves the token claims

        //echo $token->getHeader('email'); // will print "4f1g23a12aa"
        $email   = $token->getClaim('email'); // will print "http://example.com"
        $expires = $token->getClaim('exp');
        $subject = $token->getClaim('sub');


        $dataWithLeeway = new ValidationData($time, 20);
        $dataWithLeeway->setIssuer($this->oauthIssuer);
        //$dataWithLeeway->setAudience('http://example.org');
        //$dataWithLeeway->setId('4f1g23a12aa');

        //var_dump($token->validate($dataWithLeeway)); // false, because token can't be used before now() + 60, not within leeway
        if(! $token->validate($dataWithLeeway) ){
            throw new \Exception("invalid_token");
        }

        $this->tokenExpires = $expires;
        $this->tokenOwnerEmail = $email;
        $this->tokenOwner = $subject;
        $this->token = $token;

        $this->authenticated = true;
        return true;
    }
    public function getBlockAdmins(){
        return array();
    }

    public function getToken(){
        return $this->token;
    }
    public function getTokenOwnerEmail(){
        return $this->tokenOwnerEmail;
    }
    public function getTokenOwner(){
        return $this->tokenOwner;
    }
    public function getTokenExpiry(){
        return $this->tokenExpires;
    }

    public function __get($propertyName)
    {
        if (isset($this->$propertyName) ) {
            return $this->$propertyName;
        } else {
            throw new Exception("No such property");
        }
    }

    /**
     * @param $content
     *
     * @return bool
     */
    public function __isset($content) {
        echo "The {$content} property is private，the __isset() method is called automatically.<br>";
        echo  isset($this->$content);
    }

    /**
     * @return array
     */
    public function __debugInfo() {
        return [
            'tokenOwner' => $this->tokenOwner ,
            'tokenOwnerEmail' => $this->tokenOwnerEmail,
            'token' => $this->token,

        ];
    }
}



