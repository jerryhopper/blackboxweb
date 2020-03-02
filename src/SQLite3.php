<?php


class SQLiteConn
{

    static function getGravityDBFilename()
    {
        // Get possible non-standard location of FTL's database
        $FTLsettings = parse_ini_file("/etc/pihole/pihole-FTL.conf");
        if(isset($FTLsettings["GRAVITYDB"]))
        {
            return $FTLsettings["GRAVITYDB"];
        }
        else
        {
            return "/etc/pihole/gravity.db";
        }
    }

    static function getQueriesDBFilename()
    {
        // Get possible non-standard location of FTL's database
        $FTLsettings = parse_ini_file("/etc/pihole/pihole-FTL.conf");
        if(isset($FTLsettings["DBFILE"]))
        {
            return $FTLsettings["DBFILE"];
        }
        else
        {
            return "/etc/pihole/pihole-FTL.db";
        }
    }

    static function connect($filename, $mode=SQLITE3_OPEN_READONLY)
    {
        if(strlen($filename) > 0)
        {
            $db = SQLiteConn::connect_try($filename, $mode, true);
        }
        else
        {
            die("No database available");
        }
        if(is_string($db))
        {
            die("Error connecting to database\n".$db);
        }

        // Add busy timeout so methods don't fail immediately when, e.g., FTL is currently reading from the DB
        $db->busyTimeout(5000);

        return $db;
    }

    static function connect_try($filename, $mode, $trytoreconnect)
    {
        try
        {
            // connect to database
            return new SQLite3($filename, $mode);
        }
        catch (Exception $exception)
        {
            // sqlite3 throws an exception when it is unable to connect, try to reconnect after 3 seconds
            if($trytoreconnect)
            {
                sleep(3);
                return SQLiteConn::connect_try($filename, $mode, false);
            }
            else
            {
                // If we should not try again (or are already trying again!), we return the exception string
                // so the user gets it on the dashboard
                return $filename.": ".$exception->getMessage();
            }
        }
    }
}
