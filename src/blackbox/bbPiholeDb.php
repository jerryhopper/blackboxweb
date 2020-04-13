<?php



class bbPiholeDb
{
    private $databaselocation = "/etc/pihole/pihole-FTL.db";
    public $database;

    public $pdo;

    /**
     * table domainlist
     * bbPiholeDb constructor.
     * @param string $databaselocation
     * @throws Exception
     */
    function __construct($databaselocation = "/etc/pihole/pihole-FTL.db"){

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
    public function getNetwork(){

        $sql="SELECT n.id, n.name,n.hwaddr,n.macVendor ,n.firstSeen ,n.lastQuery,n.numQueries, na.ip  ,na.lastSeen
                FROM network n
                INNER JOIN network_addresses na ON
                n.id = na.network_id
                ORDER BY na.lastSeen";
        $stmt = $this->pdo->prepare($sql);
        //$stmt->bindParam('type',$type);
        try{
            $x = $stmt->execute();
        }catch(Exception $e){
            echo $e->getMessage();
        }
        return $stmt->fetchAll();
    }


}
