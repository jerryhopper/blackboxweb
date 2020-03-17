<?php


class bbConfig
{
    private $owner = null; //etc/blackbox/osbox.owner
    private $id    = null;    //etc/blackbox/osbox.id
    private $state = null; //etc/blackbox/osbox.state
    private $networkstate = null; //etc/blackbox/osbox.network

    private $networktype  = null; //  static/dynamic

    private $readablestate = null;


    function __construct()
    {
        $this->hasId();
        $this->hasOwner();
        $this->hasState();
        $this->networkType();

    }

    public function registeredToAccount(){


        return false;

    }

    public function networkConfigured(){
        if ( $this->networktype=="static" ){
            return true;
        }
        return false;
    }

    private function networkType(){
        $this->networktype = trim(exec("bash /usr/lib/osbox/stage/networkcurrent.sh"));
    }



    /**
     * @param $propertyName
     * @return bool
     */
    public function __get($propertyName)
    {
        if( isset($this->$propertyName ) ){
            return $this->$propertyName;
        } else {
            return false;
        }
    }

    /**
     * @return bool
     */
    private function hasOwner(){

        if( file_exists("/etc/osbox/osbox.owner")) {
            $this->owner = trim( $this->read("/etc/blackbox/blackbox.owner") );
            return true;
        }
        $this->owner=false;
        return false;
    }

    /**
     * @param $uid
     * @param $email
     * @return bool
     * @throws Exception
     */
    public function setOwner($uid,$email){

        $res = exec("sudo osbox owner set $uid");
        error_log("sudo osbox owner set $uid");
        return true;
        //return $this->write("/etc/osbox/osbox.owner",$uid);
    }



    /**
     * @return bool
     * @throws Exception
     */
    private function hasId(){
        if(file_exists("/etc/osbox/osbox.id")){
            $this->id = trim($this->read("/etc/osbox/osbox.id"));
            return true;
        }
        return false;
    }

    /**
     * @return bool
     * @throws Exception
     */
    private function hasState(){
        if(file_exists("/etc/osbox/osbox.state")){
            $this->state = trim($this->read("/etc/osbox/osbox.state"));
            $this->readablestate = (string )new bbState($this->state);
            return true;
        }
        return false;
    }




    /**
     * @param $file
     * @param $data
     * @return bool
     * @throws Exception
     */
    private function write($file, $data){
        if (!$handle = fopen($file, 'w')) {
            throw new \Exception("Cannot open file ($file)");
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
