<?php


class bbCommand
{
    function __construct()
    {

    }

    private function  exec (){

        return new bbExec($command);

        print_r($res);
        $res->getResult();
        $res->getCommand();
        $res->getReturnvar();
        $res->getOutput();
    }


    function network($command){



        "osbox network ".$command;

    }

}
