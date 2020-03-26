<?php

class bbCommand {

    private $result;
    private $command;
    private $returnvar;
    private $output;


    function __construct($cmd){
        $this->exec($cmd);
    }

    /**
     * Executes a osbox Shellcommand
     * @param $command
     * @return object
     */
    private function exec ($command){

        $result = exec("sudo ".$command." 2>&1" ,$output,$returnvar);

        $this->result = $result;
        $this->command = $command;
        $this->returnvar = $returnvar;
        $this->output = $output;

        return (object) array(
            "result"=>  $result,
            "command"=> $command,
            "returnvar"=>$returnvar,
            "output"=>  $output
        );

    }



    public function getResult(){
        return $this->result;
    }

    public function getCommand(){
        return $this->command;
    }

    public function getReturnvar(){
        return $this->returnvar;
    }

    public function getOutput(){
        return $this->output;
    }

}
