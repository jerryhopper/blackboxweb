<?php


class BlackBox
{
    public $config;
    public $isConfigured;
    public $isRegistered;


    function __construct()
    {
        $this->config = new bbConfig();
        $this->isConfigured = $this->config->networkConfigured();
        $this->isRegistered = $this->config->registeredToAccount();

    }


    public function exec ($command){
        $result = exec("sudo ".$command." 2>&1" ,$output,$returnvar);
        return (object) array("result"=>$result,"command"=>$command,"returnvar"=>$returnvar,"output"=>$output);
    }

    public function showPage( $templatename ){

        // if the network is configured, and device has a owner we can show the requested template
        if($this->config->networkConfigured() && $this->config->registeredToAccount() ){
            return $templatename;
        }
        // if the network is configured and we have no owner
        if(!$this->config->networkConfigured()  ){
            return "setup/index.html";
        }


        // if the network is configured and we have no owner
        return "register/index.html";
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
