<?php


class bbConfig
{
    private $owner; //etc/blackbox/blackbox.owner
    private $id;    //etc/blackbox/blackbox.id
    private $state; //etc/blackbox/blackbox.state

    private $readablestate;

    function __construct()
    {
        $this->hasId();
        $this->hasOwner();
        $this->hasState();

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

        if( file_exists("/etc/blackbox/blackbox.owner")) {
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

        $res = exec("sudo blackbox owner set $uid");
        return true;
        //return $this->write("/etc/blackbox/blackbox.owner",$uid);
    }



    /**
     * @return bool
     * @throws Exception
     */
    private function hasId(){
        if(file_exists("/etc/blackbox/blackbox.id")){
            $this->id = trim($this->read("/etc/blackbox/blackbox.id"));
            return true;
        }
        return false;
    }

    /**
     * @return bool
     * @throws Exception
     */
    private function hasState(){
        if(file_exists("/etc/blackbox/blackbox.state")){
            $this->state = trim($this->read("/etc/blackbox/blackbox.state"));
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
