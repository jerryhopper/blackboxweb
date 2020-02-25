<?php


require 'vendor/autoload.php';

#echo "xYYYYYYYYYY";

#print_r($_SERVER);

#echo "xxx";

// Create Slim app
$app = new \Slim\App();

// Fetch DI Container
$container = $app->getContainer();

// Register Twig View helper
$container['view'] = function ($c) {
    $view = new \Slim\Views\Twig('templates', [
        /*'cache' => 'path/to/cache'*/
    ]);

    // Instantiate and add Slim specific extension
    $router = $c->get('router');
    $uri = \Slim\Http\Uri::createFromEnvironment(new \Slim\Http\Environment($_SERVER));
    $view->addExtension(new \Slim\Views\TwigExtension($router, $uri));

    return $view;
};




// Define named route
$app->get('/callback', function ($request, $response, $args) {
    //return $response->withStatus(403);
    return $this->view->render($response, 'child.html', [
        'name' => $args['name']
    ]);
})->setName('profile');


// Define login route
$app->get('/login', function ($request, $response, $args) {
    //return $response->withStatus(403);
    return $this->view->render($response, '_login.html', [
        'name' => $args['name']
    ]);
})->setName('proxfile');

// Define logout route
$app->get('/logout', function ($request, $response, $args) {
    //return $response->withStatus(403);
    return $this->view->render($response, 'logout.html', [
        'name' => $args['name']
    ]);
})->setName('profxxile');




// Define home route
$app->get('/', function ($request, $response, $args) {
    //return $response->withStatus(403);
    return $this->view->render($response, 'dashboard.html', [

    ]);
})->setName('profile');

// Define home route
$app->get('/api.php', function ($request, $response, $args) {

    /*   Pi-hole: A black hole for Internet advertisements
    *    (c) 2017 Pi-hole, LLC (https://pi-hole.net)
    *    Network-wide ad blocking via your own hardware.
    *
    *    This file is copyright under the latest version of the EUPL.
    *    Please see LICENSE file for your rights under this license */

    //GET
    $allGetVars = $request->getQueryParams();

    //POST or PUT
    $allPostPutVars = $request->getParsedBody();




    $api = true;
    //header('Content-type: application/json');
    require("../admin/scripts/pi-hole/php/FTL.php");
    require("../admin/scripts/pi-hole/php/password.php");
    require("../admin/scripts/pi-hole/php/auth.php");
    #check_cors();

    $FTL_IP = "127.0.0.1";

    $data = array();

    // Common API functions
    if (isset($allGetVars['status']))
    {
        $pistatus = exec('sudo pihole status web');
        if ($pistatus == "1")
        {
            $data = array_merge($data, array("status" => "enabled"));
        }
        else
        {
            $data = array_merge($data, array("status" => "disabled"));
        }
    }
    elseif (isset($allGetVars['enable']) && $auth)
    {
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
    elseif (isset($allGetVars['disable']) && $auth)
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
    elseif (isset($allGetVars['versions']))
    {
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
    elseif (isset($allGetVars['list']))
    {
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

    // Other API functions
    //require("api_FTL.php");
    //  /var/www/admin/
    //  /var/www/blackbox/

    //require("/var/www/admin/api_FTL.php");












    if(!isset($api))
    {
        die("Direct call to api_FTL.php is not allowed!");
    }

// $FTL_IP is defined in api.php
    $socket = connectFTL($FTL_IP);

    if(!is_resource($socket))
    {
        $data = array_merge($data, array("FTLnotrunning" => true));
    }
    else
    {
        if (isset($allGetVars['type'])) {
            $data["type"] = "FTL";
        }

        if (isset($allGetVars['version'])) {
            $data["version"] = 3;
        }

        if (isset($allGetVars['summary']) || isset($allGetVars['summaryRaw']) || !count($_GET))
        {
            require_once("scripts/pi-hole/php/gravity.php");
            sendRequestFTL("stats");
            $return = getResponseFTL();

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
            $stats['gravity_last_updated'] = gravity_last_update(true);
            $data = array_merge($data,$stats);
        }

        if (isset($allGetVars['overTimeData10mins']))
        {
            sendRequestFTL("overTime");
            $return = getResponseFTL();

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
            $data = array_merge($data, $result);
        }

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

        if (isset($allGetVars['getAllQueries']) && $auth)
        {
            if(isset($allGetVars['from']) && isset($allGetVars['until']))
            {
                // Get limited time interval
                sendRequestFTL("getallqueries-time ".$allGetVars['from']." ".$allGetVars['until']);
            }
            else if(isset($allGetVars['domain']))
            {
                // Get specific domain only
                sendRequestFTL("getallqueries-domain ".$allGetVars['domain']);
            }
            else if(isset($allGetVars['client']))
            {
                // Get specific client only
                sendRequestFTL("getallqueries-client ".$allGetVars['client']);
            }
            else if(isset($allGetVars['querytype']))
            {
                // Get specific query type only
                sendRequestFTL("getallqueries-qtype ".$allGetVars['querytype']);
            }
            else if(isset($allGetVars['forwarddest']))
            {
                // Get specific forward destination only
                sendRequestFTL("getallqueries-forward ".$allGetVars['forwarddest']);
            }
            else if(is_numeric($allGetVars['getAllQueries']))
            {
                sendRequestFTL("getallqueries (".$allGetVars['getAllQueries'].")");
            }
            else
            {
                // Get all queries
                sendRequestFTL("getallqueries");
            }
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

        disconnectFTL();
    }























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



// Define whitelist route
$app->get('/whitelist', function ($request, $response, $args) {
    //return $response->withStatus(403);
    return $this->view->render($response, 'whitelist.html', [
        'name' => $args['name']
    ]);
})->setName('whitelist');

// Define blacklist route
$app->get('/blacklist', function ($request, $response, $args) {
    //return $response->withStatus(403);
    return $this->view->render($response, 'blacklist.html', [
        'name' => $args['name']
    ]);
})->setName('blacklist');

// Define blacklist route
$app->get('/network', function ($request, $response, $args) {
    //return $response->withStatus(403);
    return $this->view->render($response, 'network.html', [
        'name' => $args['name']
    ]);
})->setName('network');

// Define blacklist route
$app->get('/groups', function ($request, $response, $args) {
    //return $response->withStatus(403);
    return $this->view->render($response, 'groups.html', [
        'name' => $args['name']
    ]);
})->setName('groups');








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
