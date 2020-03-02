<?php


class BbPiholeApi
{
    private $setupVars;
    private $allGetVars;
    private $allPostPutVars;
    public $FTL;

    private $data = array();

    function __construct($request,$args,$setupVars)
    {
        $this->setupVars = $setupVars;

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
        //$this->FTL->connect("127.0.0.1");

        if( isset($this->allGetVars['summary'] ) ){
        //    $this->summary();
        }
        //print_r($this->allPostPutVars);

        //var_dump($this->allGetVars);


    }



    function getAllQueries(){

    }


    function status(){
        $pistatus = exec('sudo pihole status web');
        $data = $this->data;
        if ($pistatus == "1")
        {
            $data = array_merge($data, array("status" => "enabled"));
        }
        else
        {
            $data = array_merge($data, array("status" => "disabled"));
        }
        $this->data = $data;

        return $data;
    }

    function enable(){

        if(isset($allGetVars["auth"]))
        {
            if($allGetVars["auth"] !== $pwhash)
                die("Not authorized!");
        }
        else
        {
            // Skip token validation if explicit auth string is given
            check_csrf($_GET['token']);
        }
        exec('sudo pihole enable');
        $data = array_merge($data, array("status" => "enabled"));
        unlink("../custom_disable_timer");

    }
    function disable()
    {
        if(isset($allGetVars["auth"]))
        {
            if($allGetVars["auth"] !== $pwhash)
                die("Not authorized!");
        }
        else
        {
            // Skip token validation if explicit auth string is given
            check_csrf($allGetVars['token']);
        }
        $disable = intval($allGetVars['disable']);
        // intval returns the integer value on success, or 0 on failure
        if($disable > 0)
        {
            $timestamp = time();
            exec("sudo pihole disable ".$disable."s");
            file_put_contents("../custom_disable_timer",($timestamp+$disable)*1000);
        }
        else
        {
            exec('sudo pihole disable');
            unlink("../custom_disable_timer");
        }
        $data = array_merge($data, array("status" => "disabled"));
    }

    function versions(){
        // Determine if updates are available for Pi-hole
        // using the same script that we use for the footer
        // on the dashboard (update notifications are
        // suppressed if on development branches)
        #require "scripts/pi-hole/php/update_checker.php";
        $updates = array("core_update" => $core_update,
            "web_update" => $web_update,
            "FTL_update" => $FTL_update);
        $current = array("core_current" => $core_current,
            "web_current" => $web_current,
            "FTL_current" => $FTL_current);
        $latest = array("core_latest" => $core_latest,
            "web_latest" => $web_latest,
            "FTL_latest" => $FTL_latest);
        $branches = array("core_branch" => $core_branch,
            "web_branch" => $web_branch,
            "FTL_branch" => $FTL_branch);
        $data = array_merge($data, $updates);
        $data = array_merge($data, $current);
        $data = array_merge($data, $latest);
        $data = array_merge($data, $branches);
    }

    function list(){
        if (isset($allGetVars['add']))
        {
            if (!$auth)
                die("Not authorized!");

            // Set POST parameters and invoke script to add domain to list
            $_POST['domain'] = $allGetVars['add'];
            $_POST['list'] = $allGetVars['list'];
            #require("scripts/pi-hole/php/add.php");
        }
        elseif (isset($allGetVars['sub']))
        {
            if (!$auth)
                die("Not authorized!");

            // Set POST parameters and invoke script to remove domain from list
            $_POST['domain'] = $allGetVars['sub'];
            $_POST['list'] = $allGetVars['list'];
            #require("scripts/pi-hole/php/sub.php");
        }
        else
        {
            error_log("1");
            #require("scripts/pi-hole/php/get.php");

        }

        return;
    }
    function type(){
        $data = array();
        $data["type"] = "FTL";
        $this->setData($data);
    }
    function version(){
        $data = array();
        $data["version"] = 3;
        $this->setData($data);
    }





    function summary(){

        // scripts/pi-hole/php/gravity.php
        //require "/var/www/html/admin/scripts/pi-hole/php/FTL.php";
        //require "/var/www/html/admin/scripts/pi-hole/php/database.php";



        $this->FTL->sendrequest("stats");

        //sendRequestFTL("stats");


        //$return = getResponseFTL();
        $return = $this->FTL->getresponse();
        //return $return;
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
        $stats['gravity_last_updated'] = Gravity::last_update(true);

        $this->setData($stats);
        return $stats;
    }

    function overTimeData10mins(){


        $this->FTL->sendrequest("overTime");
        $return = $this->FTL->getresponse();

        //$this->data = $return;
        //return;
        $domains_over_time = array();
        $ads_over_time = array();
        foreach($return as $line)
        {
            $tmp = explode(" ",$line);
            $domains_over_time[intval($tmp[0])] = intval($tmp[1]);
            $ads_over_time[intval($tmp[0])] = intval($tmp[2]);
        }
        $result = array('domains_over_time' => $domains_over_time,
            'ads_over_time' => $ads_over_time);
        //$data = array_merge($data, $result);
        $this->data = $result;
        $this->setData($result);
    }



    private function setData($theData){
        $data = array_merge($this->data,$theData);
        $this->data = $data;
    }

    function result(){
        return $this->data;
    }
}
