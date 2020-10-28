#!/usr/bin/php
<?php require __DIR__ . '/../vendor/autoload.php';

/******************************************************************************

    CoronaData is a php class to analyse the worldwide corona situation
    Copyright (C) 2020  Oliver Welter  <oliver@welter.rocks>

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <https://www.gnu.org/licenses/>.

*******************************************************************************/

// Initialize autoloading
use WelterRocks\CoronaData\Client;
use WelterRocks\CoronaData\Callback;
use WelterRocks\CoronaData\CLI;
use WelterRocks\CoronaData\Exception;

// FIFO, config and PID file settings
define("PROG_NAME", "CoronaDataUpdater");
define("RUN_DIR", "/run/coronadata");
define("PID_FILE", RUN_DIR."/".PROG_NAME.".pid");

define("HOME_DIR", getenv("HOME"));
define("CONF_DIR", "/etc/coronadata");

// Handles, bools and tick counters
$daemon_terminate = false;
$worker_reload = false;

$ticks_state = 0;

$max_cast_age = 3600;

$cachetime_eu_datacast = 3600;
$cachetime_rki_datacast = 3600;
$cachetime_rki_positive = 3600;
$cachetime_cov_infocast = 3600;

$skip_eu_datacast = false;
$skip_rki_nowcast = false;
$skip_rki_positive = false;
$skip_cov_infocast = false;

$pid = null;

$log_options = LOG_CONS | LOG_NDELAY | LOG_PID;

// Create CLI object
try
{
    $cli = new CLI(PROG_NAME);
}
catch (Exception $ex)
{
    echo "FATAL ERROR: ".$ex->getMessage()."\n";
    exit(255);
}

// Trap signals for mother
$cli->register_signal(SIGTERM);
$cli->redirect_signal(SIGHUP, SIGTERM);
$cli->redirect_signal(SIGUSR1, SIGTERM);
$cli->redirect_signal(SIGUSR2, SIGTERM);
$cli->redirect_signal(SIGINT, SIGTERM);

// Usage
function usage()
{
    global $cli;
    
    $cli->exit_error(CLI::COLOR_LIGHT_RED."Usage: ".CLI::COLOR_WHITE.$cli->get_command().CLI::COLOR_LIGHT_CYAN." start|stop|reload|status|foreground".CLI::COLOR_EOL, 1);
}

// Worker reload callback
function worker_reload_callback()
{
    global $worker_reload, $cli;
    
    $cli->log("Received HUP signal, initiating worker reload", LOG_INFO);

    $worker_reload = true;
    
    return;
}

// Daemon terminate callback
function daemon_terminate_callback()
{
    global $daemon_terminate, $worker_reload, $cli;
    
    $cli->log("Received TERM signal, initiating daemon shutdown", LOG_INFO);
    
    $daemon_terminate = true;
    $worker_reload = true;
        
    return;
}

// Select config file function
function select_config_file()
{
    if ((is_dir(CONF_DIR)) && (file_exists(CONF_DIR."/coronadata.conf")))
        $config_file = CONF_DIR."/coronadata.conf";
    else
        $config_file = HOME_DIR."/.coronadatarc";

    if ($config_file == HOME_DIR."/.coronadatarc")
    {
        if ((!is_dir(HOME_DIR)) || (!file_exists(HOME_DIR."/.coronadatarc")))
	    return false;
    }
    
    return $config_file;
}

// Worker loop
function worker_loop(Client $client, $oneshot = false)
{
    global $ticks_state, $max_cast_age;
    global $cli, $worker_reload, $daemon_terminate;
    global $cachetime_eu_datacast, $cachetime_rki_nowcast, $cachetime_cov_infocast, $cachetime_rki_positive;
    global $skip_eu_datacast, $skip_rki_nowcast, $skip_rki_positive, $skip_cov_infocast;
    
    // Dispatch signals in inner loop
    $cli->signals_dispatch();

    // Update ticks_state
    $ticks_state++;
    
    if (($client->get_eu_datacast_size() == 0) || ($client->get_rki_nowcast_size() == 0) || ($client->get_cov_infocast_size() == 0))
	$ticks_max_state = 1000;
    else
        $ticks_max_state = 25000;
        
    // Is datacast autoexec disabled? Probably first run
    $datacast_autoexec_disabled = false;
    
    if (($ticks_state == $ticks_max_state) || ($oneshot))
    {
        $did_updates = 0;
        
        // Retrieve EU datacast
        if ((!$skip_eu_datacast) && (($client->get_eu_datacast_timestamp() + ($max_cast_age * 1000)) < Client::timestamp()))
        {
            $cli->log("Retrieving EU datacast.", LOG_INFO);
            $size = $client->retrieve_eu_datacast($cachetime_eu_datacast);
            
            if (!$size)
                $cli->log("Unable to fetch EU datacast. The result was empty.", LOG_ALERT);
            else
                $cli->log("EU datacast with ".$size." bytes received.", LOG_INFO);

            // Update the EU datacast store
            $cli->log("Updating EU datacast store.", LOG_INFO);
    
            $totalcount = 0;
            $successcount = 0;
            $filtercount = 0;
            $errorcount = 0;
            $errordata = null;
            
            try
            {
                // No filtering ("eu_datacast", null, null, null), so we can get worldwide datasets
                $client->update_eu_datacast_store("eu_datacast", null, null, null, true, 25, $totalcount, $successcount, $errorcount, $errordata, $filtercount, $datacast_autoexec_disabled);
                $cli->log("EU datacast store has been updated. Wrote ".$successcount." entries from ".$totalcount.", while ".$filtercount." were filtered.", LOG_INFO);
                
                if ($errorcount)
                {
                    $cli->log("There were problems writing EU datacast data. ".$errorcount." record(s) could not be written.", LOG_ALERT);
                    
                    if ($dumpfile = $client->create_error_dump("eu-datacast-error-", $errordata))
                        $cli->log("Dump file written to '".$dumpfile."'", LOG_ALERT);
                    else
                        $cli->log("Unable to write dumpfile.", LOG_ALERT);
                        
                    unset($dumpfile);
                }
                
                $did_updates++;
            }
            catch (Exception $ex)
            {
                $cli->log("Unable to update EU datacast store: ".$ex->getMessage()." in ".$ex->getFile().", line ".$ex->getLine(), LOG_ALERT);
            }
            
            unset($totalcount);
            unset($successcount);
            unset($filtercount);
            unset($errorcount);
            unset($errordata);
        }

        // Retrieve RKI nowcast
        if ((!$skip_rki_nowcast) && (($client->get_rki_nowcast_timestamp() + ($max_cast_age * 1000)) < Client::timestamp()))
        {
            $cli->log("Retrieving RKI nowcast.", LOG_INFO);
            $size = $client->retrieve_rki_nowcast($cachetime_rki_nowcast);

            if (!$size)
                $cli->log("Unable to fetch RKI nowcast. The result was empty.", LOG_ALERT);
            else
                $cli->log("RKI nowcast with ".$size." bytes received.", LOG_INFO);
            
            // Update the RKI nowcast store
            $cli->log("Updating RKI nowcast store.", LOG_INFO);
    
            $totalcount = 0;
            $successcount = 0;
            $filtercount = 0;
            $errorcount = 0;
            $errordata = null;
            
            try
            {
                // Europe, Germany is currently hardcoded, because it is the only country known to produce compatible nowcasts
                $client->update_rki_nowcast_store("rki_nowcast", "Europe", "Germany", "Germany", true, 25, $totalcount, $successcount, $errorcount, $errordata, $filtercount);
                $cli->log("RKI nowcast store has been updated. Wrote ".$successcount." entries from ".$totalcount.", while ".$filtercount." were filtered.", LOG_INFO);
                
                if ($errorcount)
                {
                    $cli->log("There were problems writing RKI nowcast data. ".$errorcount." esteem dataset(s) could not be written.", LOG_ALERT);
                    
                    if ($dumpfile = $client->create_error_dump("rki-nowcast-error-", $errordata))
                        $cli->log("Dump file written to '".$dumpfile."'", LOG_ALERT);
                    else
                        $cli->log("Unable to write dumpfile.", LOG_ALERT);
                        
                    unset($dumpfile);
                }
                
                $did_updates++;
            }
            catch (Exception $ex)
            {
                $cli->log("Unable to update RKI nowcast store: ".$ex->getMessage()." in ".$ex->getFile().", line ".$ex->getLine(), LOG_ALERT);
            }
            
            unset($totalcount);
            unset($successcount);
            unset($errorcount);
            unset($errordata);
        }
        
        // Retrieve COV infocast
        if ((!$skip_cov_infocast) && (($client->get_cov_infocast_timestamp() + ($max_cast_age * 1000)) < Client::timestamp()))
        {
            $cli->log("Retrieving COV infocast.", LOG_INFO);
            $size = $client->retrieve_cov_infocast($cachetime_cov_infocast);

            if (!$size)
                $cli->log("Unable to fetch COV infocast. The result was empty.", LOG_ALERT);
            else
                $cli->log("COV infocast with ".$size." bytes received.", LOG_INFO);
            
            // Update the COV infocast store
            $cli->log("Updating COV infocast store.", LOG_INFO);
    
            $totalcount = 0;
            $successcount = 0;
            $filtercount = 0;
            $errorcount = 0;
            $errordata = null;
            
            try
            {
                // No filtering ("eu_datacast", null, null, null), so we can get worldwide datasets
                $client->update_cov_infocast_store("cov_infocast", null, null, null, true, 25, $totalcount, $successcount, $errorcount, $errordata, $filtercount);
                $cli->log("COV infocast store has been updated. Wrote ".$successcount." entries from ".$totalcount.", while ".$filtercount." were filtered.", LOG_INFO);
                
                if ($errorcount)
                {
                    $cli->log("There were problems writing COV infocast data. ".$errorcount." informative dataset(s) could not be written.", LOG_ALERT);
                    
                    if ($dumpfile = $client->create_error_dump("cov-infocast-error-", $errordata))
                        $cli->log("Dump file written to '".$dumpfile."'", LOG_ALERT);
                    else
                        $cli->log("Unable to write dumpfile.", LOG_ALERT);
                        
                    unset($dumpfile);
                }
                
                $did_updates++;
            }
            catch (Exception $ex)
            {
                $cli->log("Unable to update COV infocast store: ".$ex->getMessage()." in ".$ex->getFile().", line ".$ex->getLine(), LOG_ALERT);
            }
            
            unset($totalcount);
            unset($successcount);
            unset($errorcount);
            unset($errordata);
        }
        
        // Datacast autoexec disabled, do recalculation
        if ((!$skip_eu_datacast) && ($datacast_autoexec_disabled))
        {
            // Update the EU datacast store
            $cli->log("Recalculating EU datacast store.", LOG_INFO);
    
            $resultcount = 0;            
            $errordata = null;
            $updatedata = null;
            
            try
            {
                // No filtering ("eu_datacast", null, null, null), so we can get worldwide datasets
                $client->recalculate_eu_datacast_store_fields("recalc_eu_datacast", true, 100000, 3, $resultcount, $updatedata, $errordata);
                $cli->log("EU datacast store has been recalculated. Updated ".$resultcount." entries.", LOG_INFO);
                
                if ((is_array($errordata)) && (count($errordata) > 0))
                {
                    $cli->log("There were problems recalculating EU datacast data. ".count($errordata)." record(s) could not be updated.", LOG_ALERT);
                    
                    if ($dumpfile = $client->create_error_dump("eu-datacast-recalc-error-", $errordata))
                        $cli->log("Dump file written to '".$dumpfile."'", LOG_ALERT);
                    else
                        $cli->log("Unable to write dumpfile.", LOG_ALERT);
                        
                    unset($dumpfile);
                }
            }
            catch (Exception $ex)
            {
                $cli->log("Unable to update EU datacast store: ".$ex->getMessage()." in ".$ex->getFile().", line ".$ex->getLine(), LOG_ALERT);
            }
            
            unset($totalcount);
            unset($successcount);
            unset($filtercount);
            unset($errorcount);
            unset($errordata);
        }

        // Retrieve RKI positive data
        if ((!$skip_rki_positive) && (($client->get_rki_positive_timestamp() + ($max_cast_age * 1000)) < Client::timestamp()))
        {
            $cli->log("Retrieving RKI positive data.", LOG_INFO);
            $size = $client->retrieve_rki_positive($cachetime_rki_positive);

            if (!$size)
                $cli->log("Unable to fetch RKI positive data. The result was empty.", LOG_ALERT);
            else
                $cli->log("RKI positive data with ".$size." bytes received.", LOG_INFO);
            
            // Update the RKI positive store
            $cli->log("Updating RKI positive data store.", LOG_INFO);
    
            $totalcount = 0;
            $successcount = 0;
            $errorcount = 0;
            $errordata = null;
            
            try
            {
                // Europe, Germany is currently hardcoded, because it is the only country known to produce compatible nowcasts
                $client->update_rki_positive_store("rki_nowcast", "Europe", "Germany", true, 25, $totalcount, $successcount, $errorcount, $errordata);
                $cli->log("RKI positive data store has been updated. Wrote ".$successcount." entries from ".$totalcount.".", LOG_INFO);
                
                if ($errorcount)
                {
                    $cli->log("There were problems writing RKI positive data. ".$errorcount." esteem dataset(s) could not be written.", LOG_ALERT);
                    
                    if ($dumpfile = $client->create_error_dump("rki-positive-error-", $errordata))
                        $cli->log("Dump file written to '".$dumpfile."'", LOG_ALERT);
                    else
                        $cli->log("Unable to write dumpfile.", LOG_ALERT);
                        
                    unset($dumpfile);
                }
                
                $did_updates++;
            }
            catch (Exception $ex)
            {
                $cli->log("Unable to update RKI positive store: ".$ex->getMessage()." in ".$ex->getFile().", line ".$ex->getLine(), LOG_ALERT);
            }
            
            unset($totalcount);
            unset($successcount);
            unset($errorcount);
            unset($errordata);
        }
        
        // If something has probably changed, recalculate the location contamination
        if ($did_updates)
        {
            $cli->log("Updating location contamination");
            
            try
            {
                $client->recalculate_location_store_fields("location_contamination", true);
                $cli->log("Update sequence finished.", LOG_INFO);
            }
            catch (Exception $ex)
            {
                $cli->log("Unable to update location store: ".$ex->getMessage()." in ".$ex->getFile().", line ".$ex->getLine(), LOG_ALERT);            
            }
        }
        
        $ticks_state = 0;
    }
    
    // Throttle CPU to prevent "doNothingLoop overloads"
    usleep(2500);
    
    return;
}

// Mother callback is triggered, when child is starting up
function mother()
{
    global $cli;
    
    $cli->write(CLI::COLOR_WHITE."Bringing up ".CLI::COLOR_LIGHT_YELLOW.PROG_NAME.CLI::COLOR_WHITE."...".CLI::COLOR_EOL, "");
    sleep(2);
    $cli->write(CLI::COLOR_LIGHT_GREEN."done".CLI::COLOR_EOL);
    
    return;
}

// Daemon callback does the hard part
function daemon()
{
    global $cli, $daemon_terminate, $worker_reload, $log_options;
    
    // Register shutdown function
    register_shutdown_function("shutdown_daemon");
    
    // Set sync signal handling
    $cli->set_async_signals(false);
    
    // Force rewrite of PID file to set childs PID
    $cli->set_pidfile(PID_FILE, $cli->get_pid(), true);
    
    // Create callbacks
    $callback_daemon_terminate = new Callback("DaemonTerminate", 100, "daemon_terminate_callback");
    $callback_worker_reload = new Callback("WorkerReload", 100, "worker_reload_callback");
    
    // Register callbacks
    $cli->register_callback(SIGTERM, "DaemonTerminate", 100, $callback_daemon_terminate);
    $cli->register_callback(SIGHUP, "WorkerReload", 100, $callback_worker_reload);
    
    // Trap signals for daemon use and clear redirects
    $cli->init_redirects();
    $cli->register_signal(SIGTERM);
    $cli->register_signal(SIGHUP);
    $cli->redirect_signal(SIGINT, SIGTERM);
    
    // Initialize logger
    $cli->init_log(LOG_DAEMON, $log_options);
    
    // Say hello to the log
    $cli->log(PROG_NAME." is starting up", LOG_INFO);
    
    // Daemon loop
    while (!$daemon_terminate)
    {
        // Select config file
        $config_file = select_config_file();
        
        // Create the client object
        $client = new Client($config_file);

        // Dispatch signals in outer loop
        $cli->signals_dispatch();

        // Send info to log, if inner worker loop begins
        $cli->log("Reached inner worker loop. Ready to serve :-)", LOG_INFO);

        // The worker loop. Just execute once on oneshot cli argument
        if ($cli->has_argument("--oneshot"))
        {
            worker_loop($client, true);
            $daemon_terminate = true;
        }
        else
        {
            while (!$worker_reload) worker_loop($client);
            
            // Send info to log, if inner worker loop breaks
            $cli->log("Left inner worker loop. Reloading.", LOG_INFO);
        }        
    
        // Reset worker reload
        $worker_reload = false;
                
        // Wait a second before reloop
        sleep(1);
    }
    
    // Send info to log, if outer loop breaks
    $cli->log("Left outer daemon loop. Waiting for shutdown.", LOG_INFO);
    
    sleep(5);
        
    remove_pid();
        
    return;
}

// Remove PID function
function remove_pid()
{
    global $cli;
    
    $cli->remove_pidfile(PID_FILE, true);
}

// Shutdown function
function shutdown_daemon()
{
    remove_pid();
}

// Checkm, whether we have to skip something
if ($cli->has_argument("--skip-eu-datacast"))
    $skip_eu_datacast = true;
if ($cli->has_argument("--skip-rki-nowcast"))
    $skip_rki_nowcast = true;
if ($cli->has_argument("--skip-cov-infocast"))
    $skip_cov_infocast = true;
if ($cli->has_argument("--skip-rki-positive"))
    $skip_rki_positive = true;

// Check usage
if ($cli->has_argument("start"))
{
    // Check for existing pid file and bound service
    if ($cli->check_pid_from_pidfile(PID_FILE, $pid))
        $cli->exit_error(CLI::COLOR_LIGHT_RED."Another instance of ".CLI::COLOR_LIGHT_YELLOW.PROG_NAME.CLI::COLOR_LIGHT_RED." is running at PID ".CLI::COLOR_LIGHT_GREEN.$pid.CLI::COLOR_EOL, 2);
    elseif (!$cli->set_pidfile(PID_FILE, $cli->get_pid()))
        $cli->exit_error(CLI::COLOR_LIGHT_RED."Unable to write PID file '".CLI::COLOR_LIGHT_YELLOW.PID_FILE.CLI::COLOR_LIGHT_RED."'".CLI::COLOR_EOL, 3);
    
    // Daemonize (fork) and prevent mother from killing her childs
    $cli->allow_zombies();
    $cli->fork("daemon", "mother", "daemon");
}
elseif ($cli->has_argument("foreground"))
{
    // Check for existing pid file and bound service
    if ($cli->check_pid_from_pidfile(PID_FILE, $pid))
        $cli->exit_error(CLI::COLOR_LIGHT_RED."Another instance of ".CLI::COLOR_LIGHT_YELLOW.PROG_NAME.CLI::COLOR_LIGHT_RED." is running at PID ".CLI::COLOR_LIGHT_GREEN.$pid.CLI::COLOR_EOL, 2);
    elseif (!$cli->set_pidfile(PID_FILE, $cli->get_pid()))
        $cli->exit_error(CLI::COLOR_LIGHT_RED."Unable to write PID file '".CLI::COLOR_LIGHT_YELLOW.PID_FILE.CLI::COLOR_LIGHT_RED."'".CLI::COLOR_EOL, 3);

    // Redirect log to console
    $log_options = LOG_CONS | LOG_NDELAY | LOG_PID | LOG_PERROR;
    
    // Start the daemon in foreground    
    daemon();
}
elseif ($cli->has_argument("stop"))
{
    // Get PID, if one and send SIGTERM to stop
    $pid = CLI::get_pid_from_pidfile(PID_FILE);
    
    if (!$pid)
        $cli->exit_error(CLI::COLOR_LIGHT_GREEN."No running process found.".CLI::COLOR_EOL, 2);
        
    $cli->write(CLI::COLOR_WHITE."Stopping ".CLI::COLOR_LIGHT_YELLOW.PROG_NAME.CLI::COLOR_WHITE." with PID ".CLI::COLOR_LIGHT_RED.$pid.CLI::COLOR_WHITE."...".CLI::COLOR_EOL, "");
    $cli->trigger_signal_to($pid, SIGTERM);    
    sleep(3);
    $cli->write(CLI::COLOR_LIGHT_GREEN."OK".CLI::COLOR_EOL);    
}
elseif ($cli->has_argument("reload"))
{
    // Get PID, if one and send SIGHUP to reload
    $pid = CLI::get_pid_from_pidfile(PID_FILE);
    
    if (!$pid)
        $cli->exit_error(CLI::COLOR_LIGHT_GREEN."No running process found.".CLI::COLOR_EOL, 2);
        
    $cli->write(CLI::COLOR_WHITE."Reloading ".CLI::COLOR_LIGHT_YELLOW.PROG_NAME.CLI::COLOR_WHITE." with PID ".CLI::COLOR_LIGHT_RED.$pid.CLI::COLOR_WHITE."...".CLI::COLOR_EOL, "");
    $cli->trigger_signal_to($pid, SIGHUP);    
    sleep(3);
    $cli->write(CLI::COLOR_LIGHT_GREEN."OK".CLI::COLOR_EOL);    
}
elseif ($cli->has_argument("status"))
{
    // Get PID, if one and display it
    $pid = CLI::get_pid_from_pidfile(PID_FILE);
    
    if (!$pid)
        $cli->exit_error(CLI::COLOR_LIGHT_GREEN."No running process found.".CLI::COLOR_EOL, 2);
        
    $cli->write(CLI::COLOR_LIGHT_YELLOW.PROG_NAME.CLI::COLOR_WHITE." is running with PID ".CLI::COLOR_LIGHT_RED.$pid.CLI::COLOR_EOL);
}
else
{
    usage();
}
        
// Thank you and now, your applause :-)
exit;
