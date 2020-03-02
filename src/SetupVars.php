<?php


class SetupVars
{
    private $setupVars;

    function __construct()
    {
        // Read setupVars.conf file
        $this->setupVars = parse_ini_file("/etc/pihole/setupVars.conf");
    }
    function get(){
        return $this->setupVars;
    }
}
