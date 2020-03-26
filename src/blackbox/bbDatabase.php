<?php



//https://www.php.net/manual/en/book.sqlite3.php

class bbDatabase{

    private $databaselocation = "/etc/osbox/osbox.db";
    public $database;

    public $pdo;

    function __construct($databaselocation = "/etc/osbox/db/osbox.db"){

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

    function dropTable($users){
        $sql = "DROP TABLE '".$users."'";
        $stmt = $this->pdo->exec($sql);
        var_dump($stmt);
    }

    function createUsersTable(){
        $sql = "CREATE TABLE users (uuid VARCHAR(40)  UNIQUE NOT NULL,email VARCHAR(80)  NOT NULL,name VARCHAR(120)  NOT NULL,owner BOOLEAN DEFAULT 'false' NOT NULL,admin BOOLEAN DEFAULT 'false' NOT NULL);";
        $stmt = $this->pdo->exec($sql);
        var_dump($stmt);
       //$stmt->execute();
    }


    /**
     * @param $tablename
     * @return bool
     */
    function tableExists($tablename){

        $sql = "SELECT count(*) AS r FROM sqlite_master WHERE type='table' AND name='".$tablename."' ";
        $stmt = $this->pdo->prepare($sql);

        try{
            $x = $stmt->execute();
        }catch(Exception $e){
            echo $e->getMessage();
        }
        $result = $stmt->fetch();

        if($result['r']=="1"){
            return true;
        }
        return false;
    }


    function getOwner(){
        $sql="SELECT * FROM users WHERE owner='true'";
        $stmt = $this->pdo->prepare($sql);
        try{
            $x = $stmt->execute();
        }catch(Exception $e){
            echo $e->getMessage();
        }
        return $stmt->fetch();
    }


    function setOwner(userObj $user,$true=true){
        if($true==true){
            $true="true";
        }else{
            $true="false";
        }

        $sql="UPDATE users SET owner=:true WHERE uuid=:uuid";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam("uuid",$user->id);
        $stmt->bindParam("true",$true);

        try{
            $x = $stmt->execute();
        }catch(Exception $e){
            echo $e->getMessage();
        }
    }



    function getAdmins(){
        $sql="SELECT * FROM users WHERE admin='true'";

        $stmt = $this->pdo->prepare($sql);

        try{
            $x = $stmt->execute();
        }catch(Exception $e){
            echo $e->getMessage();
        }
        return $stmt->fetchAll();
    }



    function setAdmin(userObj $user,$true=true){
        if($true==true){
           $true="true";
        }else{
           $true="false";
        }
        $sql="UPDATE users SET admin=:true WHERE uuid=:uuid";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam("uuid",$user->id);
        $stmt->bindParam("true",$true);

        try{
            $x = $stmt->execute();
        }catch(Exception $e){
            echo $e->getMessage();
        }
    }


    /**
     * Gets a specific user
     * @param userObj $user
     * @return mixed
     */
    function getUser(userObj $user){
        $sql="SELECT * FROM users WHERE uuid=:uuid";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam("uuid",$user->id);

        try{
            $x = $stmt->execute();
        }catch(Exception $e){
            echo $e->getMessage();
        }
        return $stmt->fetch();
    }

    /**
     * Get all users in the database.
     * @param userObj $user
     */
    function getUsers(){
        $sql="SELECT * FROM users";

        $stmt = $this->pdo->prepare($sql);

        try{
            $x = $stmt->execute();
        }catch(Exception $e){
            echo $e->getMessage();
        }
        return $stmt->fetch();
    }

    /**
     * Creates a user in the database.
     * @param userObj $user
     */
    function createUser(userObj $user){

        $sql="INSERT INTO users ( uuid,email,name) VALUES (:uuid,:email,:name)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam("uuid",$user->id);
        $stmt->bindParam("email",$user->email);
        $stmt->bindParam("name",$user->name);

        try{
            $stmt->execute();
        }catch(Exception $e){
            echo $e->getMessage();
        }
        //var_dump($x);
    }

    /**
     * Deletes a user from the database.
     * @param userObj $user
     */
    function deleteUser(userObj $user){

        $sql="DELETE FROM users WHERE uuid=:uuid";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam("uuid",$user->id);

        try{
            $stmt->execute();
        }catch(Exception $e){
            echo $e->getMessage();
        }
        //var_dump($x);
    }

    /**
     * Updates a user (name,email) in the database.
     * @param userObj $user
     */
    function updateUser(userObj $user){

        $sql="UPDATE users SET name=:name, email=:email WHERE uuid=:uuid";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam("uuid",$user->id);
        $stmt->bindParam("email",$user->email);
        $stmt->bindParam("name",$user->name);

        try{
            $stmt->execute();
        }catch(Exception $e){
            echo $e->getMessage();
        }
        //var_dump($x);
    }



}

