<?php


class bbConfig
{

    // firstboot.state
    // hardware.json

    // blackbox.id
    // blackbox.state
    // blackbox.conf

    private $state;
    private $id;
    private $owner;
    private $networkState=false;




    function __construct()
    {
        $this->state = $this->readState();
        $this->id = $this->readId();
        $this->owner = $this->readOwner();
    }

    /**
     * @return array
     */
    public function __debugInfo() {
        return [
            'state' => $this->state,
            'id' => $this->id,
            'owner' => $this->owner,
            'networkState' => $this->networkState
        ];
    }

    public function __get($propertyName){
        if( !property_exists($this,$propertyName) ){
            throw new Exception("No such property exists");
        } else {
            return $this->$propertyName;
        }
    }

    private function readOwner(){

        $file = "/etc/blackbox/blackbox.owner";
        try {
            $response = $this->fread($file);
        }catch( Exception  $e){
            $response = false;
        }
        return $response;
    }

    private function readState (){
        $file = "/etc/blackbox/blackbox.state";
        return $this->fread($file);
    }

    private function readId(){
        $file = "/etc/blackbox/blackbox.id";
        return $this->fread($file);
    }

    private function fread($file){
        if(file_exists($file)){
            $h = fopen($file,"r");
            $contents = fread($h, filesize($file));
            fclose($h);
        }else{
            throw new Exception("$file doesnt exist.");
        }
        return $contents;
    }



}
