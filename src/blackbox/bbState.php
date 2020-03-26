<?php

class bbState
{


    function __construct($state)
    {
        $this->state = $this->states( $state );
        //var_dump($this);
        //die();
    }

    public function __toString()
    {
        return  $this->state;
    }

    private function states($state){
        $states =  array(
            "0"=>"Hardware detection & device registration",
            "1"=>"Update osboxweb repo",
            "2"=>"APT install prerequisites.",
            "3"=>"Install osbox web",
            "4"=>"Lighttpd SSL config",
            "5"=>"prepare for pihole install",
            "6"=>"pihole install",
            "7"=>"pihole switch to dev branch",
            "8"=>"fix permissions & lighttpd config",
            "9"=>"dummy",
            "10"=>"Ready for shipping",
            "11"=>"static network configured",
            "12"=>"namebased host reachable",
            "13"=>"device registered to user"
        );

        return $states[(string) trim($state)];
    }

}
