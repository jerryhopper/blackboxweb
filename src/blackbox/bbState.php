<?php

class bbState
{


    function __construct($state)
    {
        $this->state = $this->states( $state );
    }

    public function __toString()
    {
        return  $this->state;
    }

    private function states($state){
        $states =  array(
            "1"=>"prescript",
            "2"=>"Automation_Custom_Script.sh",
            "3"=>"registerhardware.sh efore ip check",
            "4"=>"registerhardware.sh after ip check",
            "5"=>"registerhardware.sh sendhash ok, device registered.",
            "6"=>"blackbox install  - copy pihole configs.",
            "7"=>"blackbox install  - pihole initial install",
            "8"=>"blackbox install  - finalizing installation. ( set pihole beta, edit lighthttp.conf create postboot )",
            "9"=>"blackbox install  - readyforclient",
            "10"=>"blackbox registerdevice"
        );

        return $states[(string) trim($state)];
    }

}
