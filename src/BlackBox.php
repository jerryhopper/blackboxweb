<?php


class BlackBox
{
    public $config;

    function __construct()
    {
        $this->config = new bbConfig();

    }

    /**
     * @param $uid
     * @param $email
     * @return bool
     * @throws Exception
     */
    function setOwner($uid,$email){
        return $this->config->setOwner($uid,$email);
    }

    /**
     * @param $file
     * @param $data
     * @return bool
     * @throws Exception
     */
    private function write($file, $data){
        if (!$handle = fopen($file, 'a')) {
            throw new Exception("Cannot open file ($file)");
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
