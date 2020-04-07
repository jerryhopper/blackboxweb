<?php


class BlackBox
{
    /**
     * Configuration information
     * @var bbConfig
     */
    public $config;

    /**
     * the UserObject
     * @var userObj
     */
    public $userObject;


    /**
     * the pihole Setupvars.
     * @var SetupVars
     */
    public $setupVars;


    public $isConfigured;
    public $isRegistered;



    private $loginurl;




    /**
     * BlackBox constructor.
     *
     */
    function __construct()
    {

        /**
         * Create the Config object.
         */
        $this->config = new bbConfig();


        /**
         * Create the auth object
         */
        $this->auth = new bbAuth();


        /**
         * Create the setupvars
         */
        $vars = new SetupVars();
        $this->setupVars = $vars->get();


        try{
            $this->database = new bbDatabase();
        } catch(Exception $e){

        }


        /**
         * Get the login/out url
         */
        $this->loginurl = $this->auth->oAuthloginUrl();
        $this->logouturl = $this->auth->oAuthlogoutUrl();


        /**
         * ....
         */
        $this->isConfigured = $this->config->networkConfigured();
        $this->isRegistered = $this->config->registeredToAccount();








        $this->piholeNativeAuth = new PiholeNativeAuth($this->setupVars);

    }

    public function debug(){

        echo "<pre>";
        //print_r();
        echo "</pre>";

    }

    function test(){


        $gravitydb = new bbGravityDb();

        $result = $gravitydb->getRegexWhite();
        #$gravitydb->flushRegex();
        #$gravitydb->importRegex();





        echo "<pre>";
        var_dump($result);
        die();
        //echo "Drop tables ";
        try{
            //$this->database->dropTable("users");
        }catch(Exception $e){

            echo $e->getMessage();
            die();
        }

        echo "createUsersTable ";


        if( !$this->database->tableExists("users") ){
            echo "Table 'usersx' doesnt exist.";
            $this->database->createUsersTable();
        }


        //$this->database->createUser($this->userObject);
        //$bbdb->setAdmin($this->userObject);


        $this->database->setOwner($this->userObject,true);

        $this->database->setAdmin($this->userObject,true);


        var_dump( $this->database->getUser($this->userObject) );
    die();//"6ab331fb-e654-4de3-aa29-b403fcd557e1"
        echo "getusers";
        var_dump( $this->database->getUsers() );

        echo "getadmin";
        var_dump( $this->database->getAdmins() );


        echo "getowner";
        var_dump( $this->database->getOwner() );

        die();


        //var_dump( $this->userObject );
        //if( $x->tableExists("usersx") ){
        //    echo "Table 'usersx' doesnt exist.";
        //}

        #if( $x->tableExists("users") ){
         #   echo "Table 'users' doesnt exist.";
        #}

        die();


        // SELECT name FROM sqlite_master WHERE type='table' AND name='{table_name}';

        //$results = $db->query('SELECT bar FROM foo');

        //while ($row = $results->fetchArray()) {
        //    var_dump($row);
        //}

        //print_r($x->database);

        die("__!__");

        return $x;
    }

    /**
     * @return string
     */
    public function getLoginUrl(){
        return $this->loginurl;
    }


    public function getLogoutUrl(){
        return $this->loginout;
    }

    /**
     *
     * @param array $array
     * @return array
     */
    public function UiParameters(array $array){
        $log = "Current installation state: ".$this->getState()." (".$this->readablestate .") - ";
        $log .= "isConfigured = ".(int)$this->isConfigured." - ";
        $log .= "isRegistered = ".(int)$this->isRegistered." - ";
        if( $this->hasOwner() ){
            $log .= "Device has owner. ";
        }else{
            $log .= "device has no owner. ";
        }

        $theData = array(
            "SERVER_ADDR"=>$_SERVER['SERVER_ADDR'],
            "AUTH_LOGINURL"=>$this->getLoginUrl(),
            "STATE"=>$this->getState(),
            "AUTH"=>$this->getUserinfo(),
            "log"=>$log
        );
        return array_merge($theData,$array);
    }


    /**
     * Gets the installation state
     * sets the readablestate
     * @return int
     */
    public function getState(){
        $state = $this->config->getState();

        $readablestate = new bbState($state);
        $this->readablestate = $readablestate->state;

        return $state;
    }

    /**
     * Validates token, and populates the userObject property
     * @param $token
     * @return bool
     */
    public function validateToken($token){

        try {
            $validation = $this->auth->validate($token);
        }catch(Exception $e){
            die("token error! ".$e->getMessage());
        }

        if(!$validation){
            return false;
        }else{
            $this->userObject = new userObj($this->auth->token);

            return true;
        }

    }

    /**
     * Returns if the device is registered to a owner.
     * @return bool
     */
    public function hasOwner(){

        if($this->config->owner){
            return true;
        }

        return false;
    }


    /**
     * @param bool $property
     * @return array|bool|mixed
     */
    public function getUserinfo($property=false){


        if( !$this->auth->isAuthenticated() ){
            if($property!=false){
                return false;
            }
            return array(
                "authenticated"=>false,
            );
        }else{

            $result =  array(
                "authenticated"=>true,

                "user"=>array(
                    "id"=>$this->userObject->id,
                    "email"=>$this->userObject->email,
                    "name"=>$this->userObject->name,
                    "roles"=>$this->userObject->roles,
                    "expires"=>$this->userObject->expires
                )
            );
            if($property!=false){
                return $result['user'][$property];
            }
            return $result;
        }

    }






    public function showPage( $templatename , $request ){

        /*
            "10"=>"Ready for shipping",
            "11"=>"static network configured",
            "12"=>"namebased host reachable",
            "13"=>"device registered to user"
        */

        if( $request->getUri()->getHost()=="blackbox.surfwijzer.nl" &&
            $request->getUri()->getScheme()=="https" ){

        }

        if ( $this->getState()==10 ) {
            return "setup/index.html";
        }
        if ( $this->getState()==11 ) {
            return "setup/index.html";
        }
        if ( $this->getState()==12 ) {
            return "register/index.html";
        }

        if ( $this->getState()==13 ) {
            return $templatename;
        }

        // if the network is configured, and device has a owner we can show the requested template
        if($this->config->networkConfigured() && $this->config->registeredToAccount() ){
            return $templatename;
        }

        //&& !$this->config->registeredToAccount()
        if( !$this->config->networkConfigured() ){
            return "setup/index.html";
        }

        return "register/index.html";


    }

    /**
     * @param $userObject
     * @return bool
     * @throws Exception
     */
    public function setOwner( userObj $userObject /* $uid,$email*/){
        return $this->config->setOwner($userObject /* $uid,$email*/);
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
