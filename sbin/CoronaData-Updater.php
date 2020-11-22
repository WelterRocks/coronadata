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

$global_cachetime = 14400;
$force_cachetime = null;
$oneshot = false;

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

// Worker startup
$worker_startup = false;

// Last run timestamp and file
$last_run_timestamp = 0;
$last_run_filename = null;

// Worker loop
function worker_loop(Client $client, $oneshot = false)
{
    global $ticks_state, $worker_startup, $preload_done, $oneshot;
    global $last_run_filename, $last_run_timestamp, $force_cachetime;
    global $cli, $worker_reload, $daemon_terminate, $global_cachetime;
    
    // Dispatch signals in inner loop
    $cli->signals_dispatch();

    // Update ticks_state
    $ticks_state++;
    
    if ($worker_startup)
    {
        $worker_startup = true;
	$ticks_max_state = 1000;
    }
    else
    {
        $ticks_max_state = 25000;
    }
        
    // Is datacast autoexec disabled? Probably first run
    $datacast_autoexec_disabled = false;
    
    if (($ticks_state == $ticks_max_state) || ($oneshot))
    {
        // Check, whether this is our first run
        if ($last_run_timestamp == 0)
        {
            @touch($last_run_filename);
            
            $last_run_timestamp = filemtime($last_run_filename);
            $force_cachetime = 999999999999999;
        }
        
        // Load all stores
        if ((($last_run_timestamp + $global_cachetime) < time()) || ($oneshot))
        {	
            $cli->log("Loading stores. This will take a while. Please be patient.", LOG_INFO);
            $length = $client->load_stores((($force_cachetime !== null) ? $force_cachetime : $global_cachetime));
            
            if ($length > 0)
            {
                $cli->log("Loading done. Got ".$length." bytes.", LOG_INFO);
                
                $cli->log("Extracting and mastering locations.", LOG_INFO);                
                $client->master_locations();
                
                $cli->log("Extracting and mastering datasets.", LOG_INFO);                
                $client->master_datasets();
                
                $cli->log("Extracting and mastering testresults.", LOG_INFO);                
                $client->master_testresults();

                $count = 0;
                $any = 0;
                $errors = array();
                                
                $cli->log("Storing location records.", LOG_INFO);
                $client->save_locations($count, $any, $errors);
                $cli->log("Stored ".$count." from ".$any." location records.", LOG_INFO);
/*                
                $cli->log("Storing divi records.", LOG_INFO);
                $client->save_divis($count, $any, $errors);
                $cli->log("Stored ".$count." from ".$any." divi records.", LOG_INFO);
*/                
                $cli->log("Storing nowcast records.", LOG_INFO);
                $client->save_nowcasts($count, $any, $errors);
                $cli->log("Stored ".$count." from ".$any." nowcast records.", LOG_INFO);
                
                $cli->log("Storing dataset records.", LOG_INFO);
                $client->save_datasets($count, $any, $errors);
                $cli->log("Stored ".$count." from ".$any." dataset records.", LOG_INFO);
/*                
                $cli->log("Storing testresult records.", LOG_INFO);
                $client->save_testresults($count, $any, $errors);
                $cli->log("Stored ".$count." from ".$any." testresult records.", LOG_INFO);
*/                
                $cli->log("Database updated.", LOG_INFO);
            }
            else
            {
                $cli->log("Loading failed. Retrying later.", LOG_ALERT);
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
    global $cli, $daemon_terminate, $worker_reload, $log_options, $oneshot;
    global $last_run_filename, $last_run_timestamp;
    
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
        
        // Get last run file
        $last_run_filename = $client->get_data_store()."/.".PROG_NAME.".lastrun";
        
        // Create last run file if not exists and leave last run timestamp at zero in this case
        if (!file_exists($last_run_filename))
            @touch($last_run_filename);
        else
            $last_run_timestamp = filemtime($last_run_filename);

        // Dispatch signals in outer loop
        $cli->signals_dispatch();

        // Send info to log, if inner worker loop begins
        $cli->log("Reached inner worker loop. Ready to serve :-)", LOG_INFO);

        // The worker loop. Just execute once on oneshot cli argument
        if ($oneshot)
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

// Check, whether we have to setup some things
if ($cli->has_argument("--oneshot"))
    $oneshot = true;
    
//  This will force the cached files to be used, if they exist
if ($cli->has_argument("--force-cache"))
    $force_cachetime = 999999999999999;

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
elseif ($cli->has_argument("cron"))
{
    // Check for existing pid file and bound service
    if ($cli->check_pid_from_pidfile(PID_FILE, $pid))
        $cli->exit_error(CLI::COLOR_LIGHT_RED."Another instance of ".CLI::COLOR_LIGHT_YELLOW.PROG_NAME.CLI::COLOR_LIGHT_RED." is running at PID ".CLI::COLOR_LIGHT_GREEN.$pid.CLI::COLOR_EOL, 2);
    elseif (!$cli->set_pidfile(PID_FILE, $cli->get_pid()))
        $cli->exit_error(CLI::COLOR_LIGHT_RED."Unable to write PID file '".CLI::COLOR_LIGHT_YELLOW.PID_FILE.CLI::COLOR_LIGHT_RED."'".CLI::COLOR_EOL, 3);

    // Redirect log to console
    $log_options = LOG_CONS | LOG_NDELAY | LOG_PID | LOG_PERROR;
    
    // Start the daemon in foreground mode and use oneshot
    $oneshot = true;
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
