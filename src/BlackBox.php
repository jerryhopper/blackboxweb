<?php


class BlackBox
{
    public $config;
    public $isConfigured;
    public $isRegistered;
    public $setupVars;

    public $loginurl = "https://idp.surfwijzer.nl/oauth2/authorize?client_id=82252ce6-ad4a-4a7f-8ff3-f7074f1a58dc&response_type=code&redirect_uri=https%3A%2F%2Fapi.surfwijzer.nl%2Fblackbox%2Flogin";

    function __construct()
    {
        $this->config = new bbConfig();
        $this->isConfigured = $this->config->networkConfigured();
        $this->isRegistered = $this->config->registeredToAccount();
        $this->state = $this->config->getState();

        $this->auth = new bbAuth();
        $vars = new SetupVars();
        $this->setupVars = $vars->get();

        $this->loginurl = $this->auth->oAuthloginUrl();


        $this->piholeNativeAuth = new PiholeNativeAuth($this->setupVars);

    }

    public function getUserinfo(){

        if( !$this->auth->isAuthenticated() ){
            return array(
                "authenticated"=>false,
            );
        }else{
            return array(
                "authenticated"=>true,
                "user"=>array(
                    "userId"=>$this->auth->tokenOwner,
                    "userEmail"=>$this->auth->tokenOwnerEmail
                )
            );
        }
        //$this->BlackBox->auth->tokenOwner;
        //'useremail',$this->BlackBox->auth->tokenOwnerEmail



    }

    public function cookietoken($token){
        if( $this->auth->validate($token) ){
            return true;
        }else{
            return false;
        }
    }

    public function exec ($command){
        $result = exec("sudo ".$command." 2>&1" ,$output,$returnvar);
        return (object) array("result"=>$result,"command"=>$command,"returnvar"=>$returnvar,"output"=>$output);
    }

    public function showPage( $templatename , $request ){

        /*
            "10"=>"Ready for shipping",
            "11"=>"static network configured",
            "12"=>"namebased host reachable",
            "13"=>"device registered to user"
        */

        if( $request->getUri()->getHost()=="blackbox.surfwijzer.nl" &&
            $request->getUri()->getScheme()=="https" ){

        }

        if ( $this->state==10 ) {
            return "setup/index.html";
        }
        if ( $this->state==11 ) {
            return "setup/index.html";
        }
        if ( $this->state==12 ) {
            return "register/index.html";
        }

        if ( $this->state==13 ) {
            return $templatename;
        }

        // if the network is configured, and device has a owner we can show the requested template
        if($this->config->networkConfigured() && $this->config->registeredToAccount() ){
            return $templatename;
        }

//&& !$this->config->registeredToAccount()
        if( !$this->config->networkConfigured() ){
            return "setup/index.html";
        }

        return "register/index.html";

        //print_r($this);

        //$this->state;

        //die();

        //state = 13
        // if the network is configured, and device has a owner we can show the requested template
        //if($this->config->networkConfigured() && $this->config->registeredToAccount() ){
        //    return $templatename;
        //}
        // if the network is configured and we have no owner
        //if(!$this->config->networkConfigured()  ){
        //    return "setup/index.html";
        //}


        // if the network is configured and we have no owner
        //return "register/index.html";
    }

    /**
     * @param $uid
     * @param $email
     * @return bool
     * @throws Exception
     */
    public function setOwner($uid,$email){
        return $this->config->setOwner($uid,$email);
    }

    /**
     * @param $file
     * @param $data
     * @return bool
     * @throws Exception
     */
    private function write($file, $data){
        if (!$handle = fopen($file, 'a')) {
            throw new Exception("Cannot open file ($file)");
            exit;
        }

        // Write $somecontent to our opened file.
        if (fwrite($handle, $data) === FALSE) {
            throw new \Exception( "Cannot write to file ($file)");
            exit;
        }
        fclose($handle);
        return true;
    }

    /**
     * @param $file
     * @return mixed
     * @throws Exception
     */
    private function read($file){
        // ------------
        if( !file_exists($file) ){
            throw new \Exception("File does not exist ($file)");
        }
        $handle = fopen($file, "r");
        $contents = fread($handle, filesize($file));
        fclose($handle);

        return $contents;
    }


}
