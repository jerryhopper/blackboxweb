<?php


require 'vendor/autoload.php';

use Lcobucci\JWT\Parser;
use Lcobucci\JWT\ValidationData;

require 'src/BlackBox.php';
require 'src/blackbox/bbCommand.php';
require 'src/blackbox/bbExec.php';
require 'src/blackbox/bbState.php';
require 'src/blackbox/bbDatabase.php';
require 'src/blackbox/bbGravityDb.php';
require 'src/blackbox/bbPiholeDb.php';
require 'src/blackbox/bbAuth.php';
require 'src/blackbox/userObj.php';
require 'src/blackbox/bbConfig.php';
require 'src/FTL.php';
require 'src/Gravity.php';
require 'src/BbPiholeApi.php';
require 'src/BbPiholeApiDb.php';
require 'src/SQLite3.php';
require 'src/PiholeNativeAuth.php';
require 'src/SetupVars.php';


// Create Slim app
$app = new \Slim\App();




// Fetch DI Container
$container = $app->getContainer();



$container['BlackBox'] = function($c){
    return new BlackBox();
};

//$container['bbconfig'] = function ($c) {
  //  return new bbConfig();
//};

$container['setupVars'] = function ($c) {
    $vars = new SetupVars();
    return $vars->get();
};

// Register Twig View helper
$container['view'] = function ($c) {
    $view = new \Slim\Views\Twig('templates', [
        'cache' => false /*'path/to/cache'*/
    ]);

    // Instantiate and add Slim specific extension
    $router = $c->get('router');
    $uri = \Slim\Http\Uri::createFromEnvironment(new \Slim\Http\Environment($_SERVER));
    $view->addExtension(new \Slim\Views\TwigExtension($router, $uri));

    return $view;
};

/**
 *  Middleware that adds auth functionality
 */
$app->add(function ($request, $response, $next) {

    $allGetVars = $request->getQueryParams();
    $cookieparms = $request->getCookieParams();

    // Authentication callback middleware
    $token = false;
    $isAuthenticated = false;

    if( isset( $allGetVars['token']) ) {
        $token = $allGetVars["token"];

    }elseif( isset( $cookieparms['auth']) ){
        $token = $cookieparms['auth'];

    }

    if($token !== false){
        // Validate the token
        $isAuthenticated = $this->BlackBox->validateToken($token);

    }



    // Add userinfo to request.
    $request = $request->withAttribute('AUTH', $this->BlackBox->getUserinfo() );


    //$response->getBody()->write('BEFORE');
    $response = $next($request, $response);
    //$response->getBody()->write('AFTER');

    $response = $response->withAddedHeader('X-Osbox-state', $this->BlackBox->getState() );
    //$response = $response->withAddedHeader('X-Osbox-readablestate', (int)$this->BlackBox->readablestate );

    $response = $response->withAddedHeader('X-Osbox-authenticated', (int) $isAuthenticated );
    $response = $response->withAddedHeader('X-Osbox-hasowner', (int)$this->BlackBox->hasOwner() );


    return $response;
});




/**
 *  Callback function.
 */
$app->get('/callback', function ($request, $response, $args) {

    $expires=3590;

    $allGetVars = $request->getQueryParams();
    $token = $allGetVars["token"];

    $isAuthenticated = $this->BlackBox->validateToken($token);




    /**
     *  If user is not authenticated, redirect to error page.
     */
    if( ! $isAuthenticated ){
        /**
         *  If user is not authenticated, redirect to error page.
         */
        return $response->withRedirect( '/error');

    }elseif( ! $this->BlackBox->hasOwner()  ){
        /**
         *  If user is authenticated, but the device has no owner.
         */
        //print_r($this->BlackBox->userObject);

        $this->BlackBox->setOwner(  $this->BlackBox->userObject );

        //die();
        // set the cookie
        $setcookies = new Slim\Http\Cookies();
        $setcookies->set('auth', [
            'value' => $token,
            'expires' => $this->BlackBox->getUserinfo('expires'),
            'path' => '/',
            'domain' => 'blackbox.surfwijzer.nl',
            'httponly' => true,
            'hostonly' => false,
            'secure' => true,
            'samesite' => 'lax'
        ]);
        $response = $response->withHeader('Set-Cookie', $setcookies->toHeaders());

        return $response->withRedirect( '/firstrun');

    }else{
        /**
         *  If user is authenticated, and the device has a owner.
         */

        // set the cookie
        $setcookies = new Slim\Http\Cookies();
        $setcookies->set('auth',[
            'value' => $token,
            'expires' => $this->BlackBox->getUserinfo('expires'),
            'path' => '/',
            'domain' => 'blackbox.surfwijzer.nl',
            'httponly' => true,
            'hostonly' => false,
            'secure' => true,
            'samesite' => 'lax'
        ]);
        $setcookies->set('persistentlogin',[
            'value' => $this->BlackBox->setupVars["WEBPASSWORD"],
            'expires' => $this->BlackBox->getUserinfo('expires'),
            'path' => '/',
            'domain' => 'blackbox.surfwijzer.nl',
            'httponly' => true,
            'hostonly' => false,
            'secure' => true,
            'samesite' => 'lax'
        ]);
        $response = $response->withHeader('Set-Cookie', $setcookies->toHeaders());

        return $response->withRedirect( '/');

    }
});



/**
 *
 *
 *
 *
 *
 **/
// Define home route
$app->get('/', function ($request, $response, $args) {


    #[authenticated] => 1
    #[user][userId] => 6ab331fb-e654-4de3-aa29-b403fcd557e1
    #[user][userEmail] => hopper.jerry@gmail.com
    #print_r($request->getAttribute("AUTH"));
    #die();


    return $this->view->render( $response, $this->BlackBox->showpage( "default/dashboard.html", $request ), $this->BlackBox->UiParameters(["PAGE"=>".page_dashboard"]));
})->setName('page_dashboard');


$app->get('/firstrun', function ($request, $response, $args) {

})->setName('page_firstrun');







$app->get('/xcc', function ($request, $response, $args) {

    return $this->view->render($response, "testopmaak.html", ["SERVER_ADDR"=>$_SERVER['SERVER_ADDR']]);
});















$app->get('/api/network/scan', function ($request, $response, $args) {

    //if( $this->bbconfig->owner !=false ){
      //  return $response->withStatus(400);
    //}
    /*

    $res = $this->BlackBox->exec("osbox network current");
    $networkCurrent = $res->getResult();
    #$res->getCommand();
    #$res->getReturnvar();
    #$res->getOutput();

    */
    $res = $this->BlackBox->exec("osbox network scan");
    $AdressesInUse = $res->getOutput();



    #die("x");


    #$cmd = 'sudo osbox network scan';
    #$result = exec( $cmd ,$AdressesInUse,$returnvar);

    #var_dump($AdressesInUse);
    #die();

    $res = $this->BlackBox->exec("osbox network info");
    $result = $res->getResult();

    #$cmd = 'sudo osbox network info';
    #$result = exec( $cmd ,$output,$returnvar);
    #error_log($result);
    #die();

    $items =explode("|",$result);


    $ips    = $items[0];
    $gateway = $items[1];


    $items = explode(",",$ips);
    $network = $items[0];

    $netdetail = explode("/",$network);

    $ipaddres  = $netdetail[0];
    $netsize  = $netdetail[1];

    $usedIps=array();
    foreach($items as $ipee){
        $tmp = str_replace("/".$netsize,"",$ipee);
        if($tmp!=""){
            $usedIps[] = $tmp;
        }
    }

    #var_dump($usedIps[0]);

    #die();
    $sub = new IPv4\SubnetCalculator($ipaddres, $netsize);

    $ip_address        = $sub->getIPAddress();

    $min_host_quads  = $sub->getMinHostQuads();
    $max_host_quads  = $sub->getMaxHostQuads();

    $min = $min_host_quads[3];
    $max = $max_host_quads[3];
    $teller = $min;

    $_ip=$min_host_quads[0].".".$min_host_quads[1].".".$min_host_quads[2].".";

    $suggestIpList = array();

    // $usedIps
    #var_dump($usedIps[0]);
    #die();

    //array_pop($AdressesInUse);
    //unset($AdressesInUse[$usedIps[0]]);
    $_AdressesInUse=array();
    foreach( $AdressesInUse as $aiu){
        if( $aiu != $usedIps[0] ){
            $_AdressesInUse[]= $aiu;
        }

    }


    //$list[]=$usedIps[0];

    while ($teller <= $max) {
        if( !in_array($_ip.$teller, $_AdressesInUse) ){
            $list[] = $_ip.$teller;
            if(($teller < 10) or ($teller > 250)){

                $suggestIpList[]=$_ip.$teller;
            }
        }
        $teller++;
    }
    $list[] = $_SERVER['HTTP_HOST'];

    #print_r($list);



    //$gatewayList = array();


    #echo "<pre>";

    #echo "<h1>$cmd</h1>";
    #echo "<h2>Result</h2>";
    #print_r($result);
    #echo "<h2>Output</h2>";
    #var_dump($output);
    #echo "<h2>Returnvar</h2>";
    #var_dump($returnvar);
    //die();
    $out = array("result"=>array(
        "ip_inuse"=>$AdressesInUse,
        "ip_free"=>$list,
        "current_ip"=>$usedIps,
        "gw"=>$gateway,
        "ip_suggest"=>$suggestIpList
        )
    );


    return $response->withJson( $out );

})->setName('network/scan');

$app->get('/api/network/info', function ($request, $response, $args) {





    $res = $this->BlackBox->exec("osbox network current");
    $networkCurrent = $res->getResult();
    #$res->getCommand();
    #$res->getReturnvar();
    #$res->getOutput();


    #$networkCurrent->result; #$networkCurrent->output; #$networkCurrent->returnvar; #$networkCurrent->command;
    if ( $networkCurrent == "static" ){
        $configurationType="static";
    } else{
        $configurationType="dynamic";
    }


    $res = $this->BlackBox->exec("osbox network info");

    $networkInfo = $res->getResult();
        #$networkInfo->result; #$networkInfo->output; #$networkInfo->returnvar; #$networkInfo->command;

    //  10.0.1.4/24,10.0.1.15/24|10.0.1.1  10.0.1.4/24,|10.0.1.1
    $nwInfo = explode("|", $networkInfo );

    $result  = $nwInfo[0];
    $gateway = $nwInfo[1];

    //print_r($result);

    $NETitems = explode(",",$result);

    $NETPrimary     = $NETitems[0];
    $NETSecundary   = $NETitems[1];

    //print_r($NETitems);
    //die("xx");

    //$network = $items[0];
    //$gateway = $items[1];
    //error_log($NETPrimary);
    $netdetail = explode("/",$NETPrimary);
    $ipaddres  = $netdetail[0];
    $netsize  = $netdetail[1];

    $sub = new IPv4\SubnetCalculator($ipaddres, $netsize);
    $subnet_mask = $sub->getSubnetMask();

    $ipQuads = $sub->getIPAddressQuads();
    $minHostQuads = $sub->getMinHostQuads();
    $minHost= $sub->getMinHost();


    $out = array(
        "result"=>array(
            "ip_address"=>$ipaddres,
            "subnet_mask"=>$subnet_mask,
            "gateway"=>$gateway,
            "size"=>$netsize,
            "raw"=>$networkInfo,
            "netconfig"=>$configurationType
        )
    );
    return $response->withJson( $out );

})->setName('network/info');




$app->post('/api/network/reset', function ($request, $response, $args) {

 #   if( $this->bbconfig->owner !=false ){
        //return $response->withStatus(400);
 #   }


    // set ip
    // check if ip is in use.
    #$cmd = "sudo osbox network reset";
    #$result = exec( $cmd ,$output,$returnvar);

    $res = $this->BlackBox->exec("osbox network reset");
    $result = $res->getResult();
    #$res->getCommand();
    #$res->getReturnvar();
    #$res->getOutput();




    if ( "$result" == "ok" ){
        return $response->withStatus(200);
    } else{
        return $response->withStatus(500);
    }


})->setName('network/ip');

$app->post('/api/system/reboot', function ($request, $response, $args) {

  #  if( $this->bbconfig->owner !=false ){
        //return $response->withStatus(400);
  #  }

    // check if ip is in use.

    $res = $this->BlackBox->exec("osbox reboot");
    $result = $res->getResult();
echo $result;

    if ( "$result" == "ok" ){
        return $response->withStatus(200);
    } else{
        return $response->withStatus(200);
    }

})->setName('system/reboot');

$app->post('/api/network/set', function ($request, $response, $args) {

    if( $this->BlackBox->hasOwner() !=false ){
        //return $response->withStatus(400);
    }
    //$xx=$request->getParsedBody();
    // set ip
    // check if ip is in use.
    $xx =$request->getParsedBody();
    $IP = $xx['ip'];
    $SUBNET = $xx['net'];
    $GATEWAY = $xx['gw'];
    $SIZE = $xx['size'];


    //return $response->withJson(array("result"=> "ok" ) )->withStatus(200);
    //var_dump($xx);
    //die("xx");
    $cmd = "osbox network set $IP $SUBNET $GATEWAY $SIZE";
    #$result = exec( $cmd ,$output,$returnvar);

    $res = $this->BlackBox->exec( $cmd );
    $result = $res->getResult();

    //print_r($result);
    //$result  ="ok";
    if ( $result == "ok" ){

        return $response->withJson(array("result"=> "ok" ) )->withStatus(200);
    } else{
        return $response->withJson(array("result"=> "error","msg"=>$result ) )->withStatus(501);
    }
    return $response->withStatus(200);
})->setName('network/set');

$app->get( '/api/network/current', function ($request, $response, $args) {

  #  if( $this->bbconfig->owner !=false ){
        //return $response->withStatus(400);
  #  }


    $result = exec( $cmd ,$output,$returnvar);

    $res = $this->BlackBox->exec( $cmd );
    $result = $res->getResult();


    if ( "$result" == "static" ){
        return $response->withJson( array("result"=>$result) )->withStatus(200);
    } else{
        return $response->withJson( array("result"=>$result) )->withStatus(200);
    }
    return $response->withStatus(200);
})->setName('network/current');





// Define whitelist route
$app->get('/whitelist', function ($request, $response, $args) {
    //return $response->withStatus(403);'name' => $args['name']

    return $this->view->render($response, 'whitelist.html', [
        "SERVER_ADDR"=>$_SERVER['SERVER_ADDR'],
        "AUTH_LOGINURL"=>$this->BlackBox->loginurl,
        "STATE"=>$this->BlackBox->state,
        "AUTH"=>$request->getAttribute("AUTH"),
        "PAGE"=>".page_whitelist"
    ]);
})->setName('whitelist');

// Define blacklist route
$app->get('/blacklist', function ($request, $response, $args) {
    //return $response->withStatus(403);'name' => $args['name']

    return $this->view->render($response, 'blacklist.html', [
        "SERVER_ADDR"=>$_SERVER['SERVER_ADDR'],
        "AUTH_LOGINURL"=>$this->BlackBox->loginurl,
        "STATE"=>$this->BlackBox->state,
        "AUTH"=>$request->getAttribute("AUTH"),
        "PAGE"=>".page_blacklist"
    ]);
})->setName('page_blacklist');

// Define blacklist route
$app->get('/network', function ($request, $response, $args) {
    //return $response->withStatus(403);'name' => $args['name']

    return $this->view->render($response, 'network.html', [
        "SERVER_ADDR"=>$_SERVER['SERVER_ADDR'],
        "AUTH_LOGINURL"=>$this->BlackBox->loginurl,
        "STATE"=>$this->BlackBox->state,
        "AUTH"=>$request->getAttribute("AUTH"),
        "PAGE"=>".page_networkdevices"
    ]);
})->setName('page_networkdevices');

// Define blacklist route
$app->get('/groups', function ($request, $response, $args) {
    //return $response->withStatus(403);'name' => $args['name']

    return $this->view->render($response, 'groups.html', [
        "SERVER_ADDR"=>$_SERVER['SERVER_ADDR'],
        "AUTH_LOGINURL"=>$this->BlackBox->loginurl,
        "STATE"=>$this->BlackBox->state,
        "AUTH"=>$request->getAttribute("AUTH"),
        "PAGE"=>".page_groups"
    ]);
})->setName('page_groups');


// Define blacklist route
$app->get('/devices', function ($request, $response, $args) {
    //return $response->withStatus(403);'name' => $args['name']



    return $this->view->render($response, 'groups.html', $this->BlackBox->UiParameters(["PAGE"=>".page_uwapparaten"]) );
})->setName('page_uwapparaten');

// Define blacklist route
$app->get('/users', function ($request, $response, $args) {
    //return $response->withStatus(403);'name' => $args['name']

    return $this->view->render($response, 'users.html', $this->BlackBox->UiParameters(["PAGE"=>".page_users"]) );
})->setName('page_users');

























// Define home route
$app->get('/test', function ($request, $response, $args) {

    //print_r($_SERVER['SERVER_ADDR']);
    //echo "<pre>";

    $log = "Current installation state: ".$this->BlackBox->getState()." (".$this->BlackBox->readablestate .") <br> ";
    $log .= "isConfigured = ".(int)$this->BlackBox->isConfigured."<br>";
    $log .= "isRegistered = ".(int)$this->BlackBox->isRegistered."<br>";
    if( $this->BlackBox->hasOwner() ){
        $log .= "Device has owner. <br>";
    }else{
        $log .= "device has no owner. <br>";
    }







   // echo "Userinfo: <br>";

  //  $ui =$this->BlackBox->getUserinfo();
   // if($ui===false){
   //     echo "user is not authenticated";
  //  }else{
   //     echo "<pre>";
     //   print_r($ui);
    //    echo "</pre>";
    //}

    //$this->BlackBox->getUserinfo();







    // SELECT name FROM sqlite_master WHERE type='table' AND name='{table_name}';
    print_r($this->BlackBox->test() );



    //$this->BlackBox->debug();
    //$Blackbox = new BlackBox();
    //var_dump( $this->BlackBox->getState() );



    die();

    $fp = fopen('/etc/osbox/osbox.db', 'w+');
    $fwrite = fwrite($fp, '');
    fclose($fp);
    var_dump($fwrite);

    die("____--____");


    if( file_exists("/etc/blackbox/blackbox.state") ){

        #var_dump(file_get_contents("/etc/blackbox/blackbox.state"));
        #var_dump(file_get_contents("/etc/blackbox/hardware.json"));
        #var_dump(file_get_contents("/etc/blackbox/blackbox.id"));
        #var_dump(file_get_contents("/etc/blackbox/blackbox.conf"));
        #var_dump(file_get_contents("/etc/blackbox/firstboot.state"));
        var_dump(file_get_contents("/etc/blackbox/.staticip"));


    }else{
        die("not exist");
    }
    die("x");



    $ip = "10.0.1.200";
    $size = 24;


    $sub = new IPv4\SubnetCalculator($ip, $size);
    //$subnet_mask = $sub->getSubnetMask();
    //$min_host        = $sub->getMinHost();
    //$max_host        = $sub->getMaxHost();
    $min_host_quads  = $sub->getMinHostQuads();
    $max_host_quads  = $sub->getMaxHostQuads();

    $min = $min_host_quads[3];
    $max = $max_host_quads[3];
    $teller = $min;

    $_ip=$min_host_quads[0].".".$min_host_quads[1].".".$min_host_quads[2].".";

    $list = array();
    while ($teller <= $max) {
        $list[] = $_ip.$teller;
        $teller++;
    }


    print_r($list);



    die();
    #print_r($this->bbconfig);
    #var_dump($this->bbconfig->owner);
    //$cmd = 'sudo /usr/share/blackbox/networkinfo.sh';

    $cmd = 'sudo blackbox network reset';
    $result = exec( $cmd ,$output,$returnvar);
    echo "<pre>";

    echo "<h1>$cmd</h1>";
    echo "<h2>Result</h2>";
    print_r($result);
    echo "<h2>Output</h2>";
    var_dump($output);
    echo "<h2>Returnvar</h2>";
    var_dump($returnvar);




    $cmd = 'sudo blackbox network set x x x';
    $result = exec( $cmd ,$output,$returnvar);
    #$x = exec('sudo whoami',$y,$z);
    #$x = exec('sudo ip addr',$y,$z);
    //$phVersion = exec("cd /etc/.pihole/ && git describe --long --tags");

    #$result = exec("cd /boot && ls -latr",$output,$returnvar );
    #echo "<pre>";

    echo "<h1>$cmd</h1>";
    echo "<h2>Result</h2>";
    print_r($result);
    echo "<h2>Output</h2>";
    var_dump($output);
    echo "<h2>Returnvar</h2>";
    var_dump($returnvar);


    die();
})->setName('test');













$app->get('/register', function ($request, $response, $args) {
    //return $response->withStatus(403);
    return $this->view->render($response, 'register/index.html', []);
})->setName('register');

$app->get('/setup', function ($request, $response, $args) {
    //return $response->withStatus(403);
    return $this->view->render($response, 'setup/index.html', []);
})->setName('setup');











// Define named route
$app->get('/cxxallback', function ($request, $response, $args) {
    //return $response->withStatus(403);
    return $this->view->render($response, 'child.html', [
        'name' => $args['name']
    ]);
})->setName('profile');


// Define login route
$app->get('/login', function ($request, $response, $args) {
    //return $response->withStatus(403);

    return $response->withRedirect($this->BlackBox->getLoginUrl());

    //{{ AUTH_LOGINURL }}
    return $this->view->render($response, '_login.html', [
        'name' => $args['name']
    ]);
})->setName('proxfile');

// Define logout route
$app->get('/logout', function ($request, $response, $args) {
    //return $response->withStatus(403);

    $setcookies = new Slim\Http\Cookies();
    $setcookies->set('auth',['value' => "none", 'expires' => time() - $expires, 'path' => '/','domain' => 'blackbox.surfwijzer.nl','httponly' => true,'hostonly' => false,'secure' => true,'samesite' => 'lax']);
    //$setcookies->set('tracking', "$value");
    $response = $response->withHeader('Set-Cookie', $setcookies->toHeaders());


    $setcookies = new Slim\Http\Cookies();
    $setcookies->set('persistentlogin',['value' => "none", 'expires' => time() - $expires, 'path' => '/','domain' => 'blackbox.surfwijzer.nl','httponly' => true,'hostonly' => false,'secure' => true,'samesite' => 'lax']);

    // And for getting cookie :
    // $jwt = $request->getCookieParam('auth');
    session_unset();
    //setcookie('persistentlogin', '');

    return $response->withRedirect( '/');






    return $this->view->render($response, 'logout.html', [
        'name' => $args['name']
    ]);
})->setName('profxxile');






$app->get('/queries.php', function ($request, $response, $args) {
    //return $response->withJson( $bbapi->result() );


    return $this->view->render($response, 'queries.html',  [] );

});


$app->get('/setupvars.json', function ($request, $response, $args) {

    return $response->withJson( $this->setupVars );


    //return $this->view->render($response, 'queries.html',  [] );

});





// Define home route
$app->get('/api.php', function ($request, $response, $args) {

    $bbapi = new BbPiholeApi($request, $args, $this->BlackBox->setupVars);

    $bbapidb = new BbPiholeApiDb($request, $args, $this->BlackBox->setupVars);

    //GET
    $allGetVars = $request->getQueryParams();

    //POST or PUT
    $allPostPutVars = $request->getParsedBody();


    $api = true;

    $data = array();

    // Common API functions
    if (isset($allGetVars['status']))
    {
        $bbapi->status();
    }
    elseif (isset($allGetVars['enable']) && $auth)
    {
        $bbapi->enable();
    }
    elseif (isset($allGetVars['disable']) && $auth)
    {
        $bbapi->disable();
    }
    elseif (isset($allGetVars['versions']))
    {
        $bbapi->versions();
    }
    elseif (isset($allGetVars['list']))
    {
        $bbapi->list();
    }

    // Other API functions
    //require("api_FTL.php");
    //  /var/www/admin/
    //  /var/www/blackbox/

    //require("/var/www/admin/api_FTL.php");

    //return $response->withJson( $bbapi->result() );



//echo "xxxxxxxxxxxxxxxxxxxxxxxxxxxx";






    //if(!isset($api))
    //{
    //    die("Direct call to api_FTL.php is not allowed!");
    //}

    // $FTL_IP is defined in api.php
    //$socket = connectFTL($FTL_IP);
    //$FTL_IP="127.0.0.1";
    //$socket = FTL::connect($FTL_IP);





    $auth = true;

    if( ! $bbapi->FTL->is_resource() )
    {
        $data = array_merge($data, array("FTLnotrunning" => true));
        //echo "NOOOOOOO";
    }
    else
    {

        if (isset($allGetVars['type'])) {
            //$data["type"] = "FTL";
            $bbapi->type();
        }

        if (isset($allGetVars['version'])) {
            $bbapi->version();
        }

        if (isset($allGetVars['summary']) || isset($allGetVars['summaryRaw']) || !count($allGetVars))
        {
            $bbapi->summary();
        }


        if (isset($allGetVars['overTimeData10mins']))
        {
            //echo "overTimeData10mins";
            $bbapi->overTimeData10mins();

            //die();
        }

        if (isset($allGetVars['getAllQueries']) && $auth)
        {

            //ie("xxx");
            $bbapidb->getAllQueries();

            /*
             *            *
             *
             *
            $return = getResponseFTL();
            $allQueries = array();
            foreach($return as $line)
            {
                $tmp = explode(" ",$line);
                // UTF-8 encode domain
                $tmp[2] = utf8_encode($tmp[2]);
                // UTF-8 encode client host name
                $tmp[3] = utf8_encode($tmp[3]);;
                array_push($allQueries,$tmp);
            }

            $result = array('data' => $allQueries);
            $data = array_merge($data, $result);
            */
        }






        /*
            if (isset($allGetVars['topItems']) && $auth)
            {
                if($allGetVars['topItems'] === "audit")
                {
                    sendRequestFTL("top-domains for audit");
                }
                else if(is_numeric($allGetVars['topItems']))
                {
                    sendRequestFTL("top-domains (".$allGetVars['topItems'].")");
                }
                else
                {
                    sendRequestFTL("top-domains");
                }

                $return = getResponseFTL();
                $top_queries = array();
                foreach($return as $line)
                {
                    $tmp = explode(" ",$line);
                    $domain = utf8_encode($tmp[2]);
                    $top_queries[$domain] = intval($tmp[1]);
                }

                if($allGetVars['topItems'] === "audit")
                {
                    sendRequestFTL("top-ads for audit");
                }
                else if(is_numeric($allGetVars['topItems']))
                {
                    sendRequestFTL("top-ads (".$allGetVars['topItems'].")");
                }
                else
                {
                    sendRequestFTL("top-ads");
                }

                $return = getResponseFTL();
                $top_ads = array();
                foreach($return as $line)
                {
                    $tmp = explode(" ",$line);
                    $domain = utf8_encode($tmp[2]);
                    if(count($tmp) > 3)
                        $top_ads[$domain." (".$tmp[3].")"] = intval($tmp[1]);
                    else
                        $top_ads[$domain] = intval($tmp[1]);
                }

                $result = array('top_queries' => $top_queries,
                    'top_ads' => $top_ads);

                $data = array_merge($data, $result);
            }

            if ((isset($allGetVars['topClients']) || isset($allGetVars['getQuerySources'])) && $auth)
            {

                if(isset($allGetVars['topClients']))
                {
                    $number = $allGetVars['topClients'];
                }
                elseif(isset($allGetVars['getQuerySources']))
                {
                    $number = $allGetVars['getQuerySources'];
                }

                if(is_numeric($number))
                {
                    sendRequestFTL("top-clients (".$number.")");
                }
                else
                {
                    sendRequestFTL("top-clients");
                }

                $return = getResponseFTL();
                $top_clients = array();
                foreach($return as $line)
                {
                    $tmp = explode(" ",$line);
                    $clientip = utf8_encode($tmp[2]);
                    if(count($tmp) > 3 && strlen($tmp[3]) > 0)
                    {
                        $clientname = utf8_encode($tmp[3]);
                        $top_clients[$clientname."|".$clientip] = intval($tmp[1]);
                    }
                    else
                        $top_clients[$clientip] = intval($tmp[1]);
                }

                $result = array('top_sources' => $top_clients);
                $data = array_merge($data, $result);
            }

                        if (isset($allGetVars['topClientsBlocked']) && $auth)
                        {

                            if(isset($allGetVars['topClientsBlocked']))
                            {
                                $number = $allGetVars['topClientsBlocked'];
                            }

                            if(is_numeric($number))
                            {
                                sendRequestFTL("top-clients blocked (".$number.")");
                            }
                            else
                            {
                                sendRequestFTL("top-clients blocked");
                            }

                            $return = getResponseFTL();
                            $top_clients = array();
                            foreach($return as $line)
                            {
                                $tmp = explode(" ",$line);
                                $clientip = utf8_encode($tmp[2]);
                                if(count($tmp) > 3 && strlen($tmp[3]) > 0)
                                {
                                    $clientname = utf8_encode($tmp[3]);
                                    $top_clients[$clientname."|".$clientip] = intval($tmp[1]);
                                }
                                else
                                    $top_clients[$clientip] = intval($tmp[1]);
                            }

                            $result = array('top_sources_blocked' => $top_clients);
                            $data = array_merge($data, $result);
                        }

                        if (isset($allGetVars['getForwardDestinations']) && $auth)
                        {
                            if($allGetVars['getForwardDestinations'] === "unsorted")
                            {
                                sendRequestFTL("forward-dest unsorted");
                            }
                            else
                            {
                                sendRequestFTL("forward-dest");
                            }
                            $return = getResponseFTL();
                            $forward_dest = array();
                            foreach($return as $line)
                            {
                                $tmp = explode(" ",$line);
                                $forwardip = utf8_encode($tmp[2]);
                                if(count($tmp) > 3 && strlen($tmp[3]) > 0)
                                {
                                    $forwardname = utf8_encode($tmp[3]);
                                    $forward_dest[$forwardname."|".$forwardip] = floatval($tmp[1]);
                                }
                                else
                                    $forward_dest[$forwardip] = floatval($tmp[1]);
                            }

                            $result = array('forward_destinations' => $forward_dest);
                            $data = array_merge($data, $result);
                        }

                        if (isset($allGetVars['getQueryTypes']) && $auth)
                        {
                            sendRequestFTL("querytypes");
                            $return = getResponseFTL();
                            $querytypes = array();
                            foreach($return as $ret)
                            {
                                $tmp = explode(": ",$ret);
                                // Reply cannot contain non-ASCII characters
                                $querytypes[$tmp[0]] = floatval($tmp[1]);
                            }

                            $result = array('querytypes' => $querytypes);
                            $data = array_merge($data, $result);
                        }

                        if (isset($allGetVars['getCacheInfo']) && $auth)
                        {
                            sendRequestFTL("cacheinfo");
                            $return = getResponseFTL();
                            $cacheinfo = array();
                            foreach($return as $ret)
                            {
                                $tmp = explode(": ",$ret);
                                // Reply cannot contain non-ASCII characters
                                $cacheinfo[$tmp[0]] = floatval($tmp[1]);
                            }

                            $result = array('cacheinfo' => $cacheinfo);
                            $data = array_merge($data, $result);
                        }



                        if(isset($allGetVars["recentBlocked"]))
                        {
                            sendRequestFTL("recentBlocked");
                            die(utf8_encode(getResponseFTL()[0]));
                            unset($data);
                        }

                        if (isset($allGetVars['getForwardDestinationNames']) && $auth)
                        {
                            sendRequestFTL("forward-names");
                            $return = getResponseFTL();
                            $forward_dest = array();
                            foreach($return as $line)
                            {
                                $tmp = explode(" ",$line);
                                $forwardip = utf8_encode($tmp[2]);
                                if(count($tmp) > 3)
                                {
                                    $forwardname = utf8_encode($tmp[3]);
                                    $forward_dest[$forwardname."|".$forwardip] = floatval($tmp[1]);
                                }
                                else
                                {
                                    $forward_dest[$forwardip] = floatval($tmp[1]);
                                }
                            }

                            $result = array('forward_destinations' => $forward_dest);
                            $data = array_merge($data, $result);
                        }

                        if (isset($allGetVars['overTimeDataQueryTypes']) && $auth)
                        {
                            sendRequestFTL("QueryTypesoverTime");
                            $return = getResponseFTL();
                            $over_time = array();

                            foreach($return as $line)
                            {
                                $tmp = explode(" ",$line);
                                for ($i=0; $i < count($tmp)-1; $i++) {
                                    $over_time[intval($tmp[0])][$i] = floatval($tmp[$i+1]);
                                }
                            }
                            $result = array('over_time' => $over_time);
                            $data = array_merge($data, $result);
                        }

                        if (isset($allGetVars['getClientNames']) && $auth)
                        {
                            sendRequestFTL("client-names");
                            $return = getResponseFTL();
                            $client_names = array();
                            foreach($return as $line)
                            {
                                $tmp = explode(" ", $line);
                                $client_names[] = array(
                                    "name" => utf8_encode($tmp[0]),
                                    "ip" => utf8_encode($tmp[1])
                                );
                            }

                            $result = array('clients' => $client_names);
                            $data = array_merge($data, $result);
                        }

                        if (isset($allGetVars['overTimeDataClients']) && $auth)
                        {
                            sendRequestFTL("ClientsoverTime");
                            $return = getResponseFTL();
                            $over_time = array();

                            foreach($return as $line)
                            {
                                $tmp = explode(" ",$line);
                                for ($i=0; $i < count($tmp)-1; $i++) {
                                    $over_time[intval($tmp[0])][$i] = floatval($tmp[$i+1]);
                                }
                            }
                            $result = array('over_time' => $over_time);
                            $data = array_merge($data, $result);
                        }
                */

        $bbapi->FTL->disconnect();
 //       FTL::disconnect();
    }


    return $response->withJson( $bbapi->result() );




















    if(isset($allGetVars["jsonForceObject"]))
    {
        echo json_encode($data, JSON_FORCE_OBJECT);
        #echo "-----xxxxxx---------";
    }
    else
    {
        echo json_encode($data);
        #echo "--------------";
    }




    return $response->withStatus(200);

    //return $this->view->render($response, 'dashboard.html', [

    //]);
})->setName('api');







$app->get('/api_db.php', function ($request, $response, $args) {

    $bbapi = new BbPiholeApiDb($request, $args, $this->setupVars);

    $auth=true;

    //GET
    $allGetVars = $request->getQueryParams();

    //POST or PUT
    $allPostPutVars = $request->getParsedBody();
    //var_dump($x);




    #$QUERYDB = getQueriesDBFilename();
    #$db = SQLite3_connect($QUERYDB);

    if(isset($allGetVars["network"]) && $auth)
    {
        $bbapi->network();
    }


    if (isset($allGetVars['getAllQueries']) && $auth)
    {
        $bbapi->getAllQueries();
    }

    return $response->withJson( $bbapi->result() );
    die();


    if (isset($allGetVars['topClients']) && $auth)
    {
        // $from = intval($_GET["from"]);
        $limit = "";
        if(isset($_GET["from"]) && isset($_GET["until"]))
        {
            $limit = "WHERE timestamp >= :from AND timestamp <= :until";
        }
        elseif(isset($_GET["from"]) && !isset($_GET["until"]))
        {
            $limit = "WHERE timestamp >= :from";
        }
        elseif(!isset($_GET["from"]) && isset($_GET["until"]))
        {
            $limit = "WHERE timestamp <= :until";
        }
        $stmt = $db->prepare('SELECT client,count(client) FROM queries '.$limit.' GROUP by client order by count(client) desc limit 20');
        $stmt->bindValue(":from", intval($_GET['from']), SQLITE3_INTEGER);
        $stmt->bindValue(":until", intval($_GET['until']), SQLITE3_INTEGER);
        $results = $stmt->execute();

        $clientnums = array();

        if(!is_bool($results))
            while ($row = $results->fetchArray())
            {
                // Try to resolve host name and convert to UTF-8
                $c = utf8_encode(resolveHostname($row[0],false));

                if(array_key_exists($c, $clientnums))
                {
                    // Entry already exists, add to it (might appear multiple times due to mixed capitalization in the database)
                    $clientnums[$c] += intval($row[1]);
                }
                else
                {
                    // Entry does not yet exist
                    $clientnums[$c] = intval($row[1]);
                }
            }

        // Sort by number of hits
        arsort($clientnums);

        // Extract only the first ten entries
        $clientnums = array_slice($clientnums, 0, 10);

        $result = array('top_sources' => $clientnums);
        $data = array_merge($data, $result);
    }

    if (isset($allGetVars['topDomains']) && $auth)
    {
        $limit = "";

        if(isset($_GET["from"]) && isset($_GET["until"]))
        {
            $limit = " AND timestamp >= :from AND timestamp <= :until";
        }
        elseif(isset($_GET["from"]) && !isset($_GET["until"]))
        {
            $limit = " AND timestamp >= :from";
        }
        elseif(!isset($_GET["from"]) && isset($_GET["until"]))
        {
            $limit = " AND timestamp <= :until";
        }
        $stmt = $db->prepare('SELECT domain,count(domain) FROM queries WHERE (STATUS == 2 OR STATUS == 3)'.$limit.' GROUP by domain order by count(domain) desc limit 20');
        $stmt->bindValue(":from", intval($_GET['from']), SQLITE3_INTEGER);
        $stmt->bindValue(":until", intval($_GET['until']), SQLITE3_INTEGER);
        $results = $stmt->execute();

        $domains = array();

        if(!is_bool($results))
            while ($row = $results->fetchArray())
            {
                // Convert domain to lower case UTF-8
                $c = utf8_encode(strtolower($row[0]));
                if(array_key_exists($c, $domains))
                {
                    // Entry already exists, add to it (might appear multiple times due to mixed capitalization in the database)
                    $domains[$c] += intval($row[1]);
                }
                else
                {
                    // Entry does not yet exist
                    $domains[$c] = intval($row[1]);
                }
            }

        // Sort by number of hits
        arsort($domains);

        // Extract only the first ten entries
        $domains = array_slice($domains, 0, 10);

        $result = array('top_domains' => $domains);
        $data = array_merge($data, $result);
    }

    if (isset($allGetVars['topAds']) && $auth)
    {
        $limit = "";

        if(isset($_GET["from"]) && isset($_GET["until"]))
        {
            $limit = " AND timestamp >= :from AND timestamp <= :until";
        }
        elseif(isset($_GET["from"]) && !isset($_GET["until"]))
        {
            $limit = " AND timestamp >= :from";
        }
        elseif(!isset($_GET["from"]) && isset($_GET["until"]))
        {
            $limit = " AND timestamp <= :until";
        }
        $stmt = $db->prepare('SELECT domain,count(domain) FROM queries WHERE (STATUS == 1 OR STATUS == 4)'.$limit.' GROUP by domain order by count(domain) desc limit 10');
        $stmt->bindValue(":from", intval($_GET['from']), SQLITE3_INTEGER);
        $stmt->bindValue(":until", intval($_GET['until']), SQLITE3_INTEGER);
        $results = $stmt->execute();

        $addomains = array();

        if(!is_bool($results))
            while ($row = $results->fetchArray())
            {
                $addomains[utf8_encode($row[0])] = intval($row[1]);
            }
        $result = array('top_ads' => $addomains);
        $data = array_merge($data, $result);
    }

    if (isset($allGetVars['getMinTimestamp']) && $auth)
    {
        $results = $db->query('SELECT MIN(timestamp) FROM queries');

        if(!is_bool($results))
            $result = array('mintimestamp' => $results->fetchArray()[0]);
        else
            $result = array();

        $data = array_merge($data, $result);
    }

    if (isset($allGetVars['getMaxTimestamp']) && $auth)
    {
        $results = $db->query('SELECT MAX(timestamp) FROM queries');

        if(!is_bool($results))
            $result = array('maxtimestamp' => $results->fetchArray()[0]);
        else
            $result = array();

        $data = array_merge($data, $result);
    }

    if (isset($allGetVars['getQueriesCount']) && $auth)
    {
        $results = $db->query('SELECT COUNT(timestamp) FROM queries');

        if(!is_bool($results))
            $result = array('count' => $results->fetchArray()[0]);
        else
            $result = array();

        $data = array_merge($data, $result);
    }

    if (isset($allGetVars['getDBfilesize']) && $auth)
    {
        $filesize = filesize("/etc/pihole/pihole-FTL.db");
        $result = array('filesize' => $filesize);
        $data = array_merge($data, $result);
    }

    if (isset($allGetVars['getGraphData']) && $auth)
    {
        $limit = "";

        if(isset($_GET["from"]) && isset($_GET["until"]))
        {
            $limit = " AND timestamp >= :from AND timestamp <= :until";
        }
        elseif(isset($_GET["from"]) && !isset($_GET["until"]))
        {
            $limit = " AND timestamp >= :from";
        }
        elseif(!isset($_GET["from"]) && isset($_GET["until"]))
        {
            $limit = " AND timestamp <= :until";
        }

        $interval = 600;

        if(isset($_GET["interval"]))
        {
            $q = intval($_GET["interval"]);
            if($q > 10)
                $interval = $q;
        }

        // Round $from and $until to match the requested $interval
        $from = intval((intval($_GET['from'])/$interval)*$interval);
        $until = intval((intval($_GET['until'])/$interval)*$interval);

        // Count permitted queries in intervals
        $stmt = $this->db->prepare('SELECT (timestamp/:interval)*:interval interval, COUNT(*) FROM queries WHERE (status != 0 )'.$limit.' GROUP by interval ORDER by interval');
        $stmt->bindValue(":from", $from, SQLITE3_INTEGER);
        $stmt->bindValue(":until", $until, SQLITE3_INTEGER);
        $stmt->bindValue(":interval", $interval, SQLITE3_INTEGER);
        $results = $stmt->execute();

        // Parse the DB result into graph data, filling in missing interval sections with zero
        function parseDBData($results, $interval, $from, $until) {
            $data = array();

            if(!is_bool($results)) {
                // Read in the data
                while($row = $results->fetchArray()) {
                    // $data[timestamp] = value_in_this_interval
                    $data[$row[0]] = intval($row[1]);
                }
            }

            return $data;
        }

        $domains = parseDBData($results, $interval, $from, $until);

        $result = array('domains_over_time' => $domains);
        $data = array_merge($data, $result);

        // Count blocked queries in intervals
        $stmt = $db->prepare('SELECT (timestamp/:interval)*:interval interval, COUNT(*) FROM queries WHERE (status == 1 OR status == 4 OR status == 5)'.$limit.' GROUP by interval ORDER by interval');
        $stmt->bindValue(":from", $from, SQLITE3_INTEGER);
        $stmt->bindValue(":until", $until, SQLITE3_INTEGER);
        $stmt->bindValue(":interval", $interval, SQLITE3_INTEGER);
        $results = $stmt->execute();

        $addomains = parseDBData($results, $interval, $from, $until);

        $result = array('ads_over_time' => $addomains);
        $data = array_merge($data, $result);
    }


    //$bbapi->result();
    return $response->withJson( $bbapi->result() );

    if(isset($_GET["jsonForceObject"]))
    {
        echo json_encode($data, JSON_FORCE_OBJECT);
    }
    else
    {
        echo json_encode($data);
    }





});







// Define named route
$app->get('/hello/{name}', function ($request, $response, $args) {
    return $this->view->render($response, 'profile.html', [
        'name' => $args['name']
    ]);
})->setName('profile');

// Render from string
$app->get('/hi/{name}', function ($request, $response, $args) {
    $str = $this->view->fetchFromString('<p>Hi, my name is {{ name }}.</p>', [
        'name' => $args['name']
    ]);
    $response->getBody()->write($str);
    return $response;
});

// Run app
$app->run();
