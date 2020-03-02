<?php


class PiholeNativeAuth
{
    private $setupVars;

    function __construct($setupVars)
    {
        $this->setupVars= $setupVars;



    }

    public function logout (){
        session_unset();
        setcookie('persistentlogin', '');
        header('Location: index.php');
        exit();

    }

    function x(){
        $wrongpassword = false;
        $auth = false;

        // Test if password is set
        if(strlen($pwhash) > 0)
        {
            // Check for and authorize from persistent cookie
            if (isset($_COOKIE["persistentlogin"]))
            {
                if ($pwhash === $_COOKIE["persistentlogin"])
                {
                    $auth = true;
                    // Refresh cookie with new expiry
                    setcookie('persistentlogin', $pwhash, time()+60*60*24*7);
                }
                else
                {
                    // Invalid cookie
                    $auth = false;
                    setcookie('persistentlogin', '');
                }
            }
            // Compare doubly hashes password input with saved hash
            else if(isset($_POST["pw"]))
            {
                $postinput = hash('sha256',hash('sha256',$_POST["pw"]));
                if(hash_equals($pwhash, $postinput))
                {
                    $_SESSION["hash"] = $pwhash;

                    // Login successful, redirect the user to the homepage to discard the POST request
                    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_SERVER['QUERY_STRING'] === 'login') {
                        // Set persistent cookie if selected
                        if (isset($_POST['persistentlogin']))
                        {
                            setcookie('persistentlogin', $pwhash, time()+60*60*24*7);
                        }
                        header('Location: index.php');
                        exit();
                    }

                    $auth = true;
                }
                else
                {
                    $wrongpassword = true;
                }
            }
            // Compare auth hash with saved hash
            else if (isset($_SESSION["hash"]))
            {
                if(hash_equals($pwhash, $_SESSION["hash"]))
                    $auth = true;
            }
            // API can use the hash to get data without logging in via plain-text password
            else if (isset($api) && isset($_GET["auth"]))
            {
                if(hash_equals($pwhash, $_GET["auth"]))
                    $auth = true;
            }
            else
            {
                // Password or hash wrong
                $auth = false;
            }
        }
        else
        {
            // No password set
            $auth = true;
        }


    }
}
