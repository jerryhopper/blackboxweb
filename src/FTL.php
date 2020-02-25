<?php


class FTL
{
    var $socket;


    function connect($address, $port=4711){
        if($address == "127.0.0.1")
        {
            // Read port
            $portfile = file_get_contents("/var/run/pihole-FTL.port");
            if(is_numeric($portfile))
                $port = intval($portfile);
        }

        // Open Internet socket connection
        $this->socket = @fsockopen($address, $port, $errno, $errstr, 1.0);

        return $this->socket;
    }


    function disconnect(){
        //global $socket;
        fclose($this->socket);
    }


    function sendrequest($requestin){
        $request = ">".$requestin;
        fwrite($this->socket, $request) or die("Could not send data to server\n");
    }

    function getresponse(){
        $response = [];

        while(true)
        {
            $out = fgets($this->socket);
            if(strrpos($out,"---EOM---") !== false)
                break;

            $out = rtrim($out);
            if(strlen($out) > 0)
                $response[] = $out;
        }

        return $response;
    }
}
