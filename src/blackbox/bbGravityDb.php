<?php


class bbGravityDb{
    private $databaselocation = "/etc/pihole/gravity.db";
    public $database;

    public $pdo;

    /**
     * table domainlist
     * column: type
     * integer
     * 0 = exact whitelist
     * 1 = exact blacklist
     * 2 = regex whitelist
     * 3 = regex blacklist
     *
     * column: enabled
     * bool
     *
     * column date_added  epoch
     * column date_modified  epoch
     * column comment sting
     *
     *
     * bbGravityDb constructor.
     * @param string $databaselocation
     * @throws Exception
     */
    function __construct($databaselocation = "/etc/pihole/gravity.db"){

        if(!file_exists($databaselocation)) {
            throw new Exception("Databasexfile not found.");
        }

        try {
            $pdo = new \PDO("sqlite:" . $databaselocation);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        } catch (\PDOException $e) {
            // handle the exception here
            die($e->getMessage());
        }

        $this->pdo = $pdo;
        //$this->database = $this->connect($databaselocation);

    }

    /**
     * @return array
     */
    private function getDomains($type){

        $sql="SELECT * FROM domainlist WHERE type=:type";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam('type',$type);
        try{
            $x = $stmt->execute();
        }catch(Exception $e){
            echo $e->getMessage();
        }
        return $stmt->fetchAll();
    }
    /**
     * @return array
     */
    private function flushDomains($type){
        $sql="DELETE FROM domainlist WHERE type=:type";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam('type',$type);
        try{
            $x = $stmt->execute();
        }catch(Exception $e){
            echo $e->getMessage();
        }
        return $x;
    }


    private function importDomains(array $array,bool $type=false){

        foreach($array as $item){
            $item;
        }
    }


    public function getGroups(){

        $sql="SELECT * FROM [group]";

        $stmt = $this->pdo->prepare($sql);
        //$stmt->bindParam('type',$type);
        try{
            $x = $stmt->execute();
        }catch(Exception $e){
            echo $e->getMessage();
        }
        return $stmt->fetchAll();
    }

    public function getClients(){

        $sql="SELECT * FROM [client]";

        $stmt = $this->pdo->prepare($sql);
        //$stmt->bindParam('type',$type);
        try{
            $x = $stmt->execute();
        }catch(Exception $e){
            echo $e->getMessage();
        }
        return $stmt->fetchAll();
    }



    /**
     * @return array
     */
    function getExactWhite(){
        return $this->getDomains(0);
    }
    function flushExactWhite(){
        return $this->flushDomains(0);
    }



    /**
     * @return array
     */
    function getExactBlack(){
        return $this->getDomains(1);
    }
    function flushExactBlack(){
        return $this->flushDomains(1);
    }


    /**
     * @return array
     */
    function getRegexWhite(){
        return $this->getDomains(2);
    }
    function flushRegexWhite(){
        return $this->flushDomains(2);
    }


    /**
     * @return array
     */
    function getRegexBlack(){
        return $this->getDomains(3);
    }
    function flushRegexBlack(){
        return $this->flushDomains(3);
    }
}











