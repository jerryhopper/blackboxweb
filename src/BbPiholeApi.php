<?php


class BbPiholeApi
{

    private $allGetVars;
    private $allPostPutVars;

    function __construct($request,$args)
    {
        $this->FTL = new FTL();

        $api = true;
        //require("/var/www/html/admin/api_FTL.php");

        //GET
        $this->allGetVars = $request->getQueryParams();
        //POST or PUT
        $this->allPostPutVars = $request->getParsedBody();

        #foreach( $this->allGetVars as $k=>$v ){
        #    $k
        #}

        if( isset($this->allGetVars['summary'] ) ){
            $this->summary();
        }
        //print_r($this->allPostPutVars);

        //var_dump($this->allGetVars);


    }

    function summary(){

        // scripts/pi-hole/php/gravity.php
        //require "/var/www/html/admin/scripts/pi-hole/php/FTL.php";
        //require "/var/www/html/admin/scripts/pi-hole/php/database.php";
        function gravity_last_update($raw = false)
        {
            $db = SQLite3_connect(getGravityDBFilename());
            $date_file_created_unix = $db->querySingle("SELECT value FROM info WHERE property = 'updated';");
            if($date_file_created_unix === false)
            {
                if($raw)
                {
                    // Array output
                    return array("file_exists" => false);
                }
                else
                {
                    // String output
                    return "Gravity database not available";
                }
            }
            // Now that we know that $date_file_created_unix is a valid response, we can convert it to an integer
            $date_file_created_unix = intval($date_file_created_unix);
            $date_file_created = date_create("@".$date_file_created_unix);
            $date_now = date_create("now");
            $gravitydiff = date_diff($date_file_created,$date_now);
            if($raw)
            {
                // Array output
                return array(
                    "file_exists"=> true,
                    "absolute" => $date_file_created_unix,
                    "relative" => array(
                        "days" =>  intval($gravitydiff->format("%a")),
                        "hours" =>  intval($gravitydiff->format("%H")),
                        "minutes" =>  intval($gravitydiff->format("%I")),
                    )
                );
            }

            if($gravitydiff->d > 1)
            {
                // String output (more than one day ago)
                return $gravitydiff->format("Blocking list updated %a days, %H:%I (hh:mm) ago");
            }
            elseif($gravitydiff->d == 1)
            {
                // String output (one day ago)
                return $gravitydiff->format("Blocking list updated one day, %H:%I (hh:mm) ago");
            }

            // String output (less than one day ago)
            return $gravitydiff->format("Blocking list updated %H:%I (hh:mm) ago");
        }

        $this->FTL->connect("127.0.0.1");

        $this->FTL->sendrequest("stats");

        //sendRequestFTL("stats");


        //$return = getResponseFTL();
        $return = $this->getResponseFTL();

        $stats = [];
        foreach($return as $line)
        {
            $tmp = explode(" ",$line);

            if(($tmp[0] === "domains_being_blocked" && !is_numeric($tmp[1])) || $tmp[0] === "status")
            {
                $stats[$tmp[0]] = $tmp[1];
                continue;
            }

            if(isset($allGetVars['summary']))
            {
                if($tmp[0] !== "ads_percentage_today")
                {
                    $stats[$tmp[0]] = number_format($tmp[1]);
                }
                else
                {
                    $stats[$tmp[0]] = number_format($tmp[1], 1, '.', '');
                }
            }
            else
            {
                $stats[$tmp[0]] = floatval($tmp[1]);
            }
        }
        $stats['gravity_last_updated'] = gravity_last_update(true);
        $data = array_merge($data,$stats);

        return $data;
    }

    function result(){
        return $this->data;
    }
}
