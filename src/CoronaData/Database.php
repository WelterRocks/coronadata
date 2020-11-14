<?php namespace WelterRocks\CoronaData;

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

use WelterRocks\CoronaData\Table\Locations;
use WelterRocks\CoronaData\Table\Datasets;
use WelterRocks\CoronaData\Table\Testresults;
use WelterRocks\CoronaData\Table\Nowcasts;
use WelterRocks\CoronaData\Table\Grafana;
use WelterRocks\CoronaData\Config;
use WelterRocks\CoronaData\Exception;

class Database
{
    private $db = null;
    
    private $config = null;
    private $config_file = null;
    
    private $table_status = null;
    private $table_status_timestamp = null;
    
    private $transactions = null;
    
    public function esc($str)
    {
        return $this->db->escape_string($str);
    }
    
    public function new_location()
    {
        return new Locations($this->db);
    }
    
    public function new_dataset()
    {
        return new Datasets($this->db);
    }
    
    public function new_testresult()
    {
        return new Testresults($this->db);
    }
    
    public function new_nowcast()
    {
        return new Nowcasts($this->db);
    }
    
    public function new_grafana()
    {
        return new Grafana($this->db);
    }
    
    private function check_init()
    {    
        if (!is_object($this->db))
            throw new Exception("Database not initialized");
            
        return true;
    }
    
    private function get_timestamp($resolution = 1000)
    {
        return round((microtime(true) * $resolution));
    }
    
    private function get_table_engine($table)
    {
        $this->update_table_status();
        
        if (isset($this->table_status->$table))
          return $this->table_status->$table->Engine;
        
        return null;    
    }
    
    private function is_table($table)
    {
        $engine = $this->get_table_engine($table);
        
        if ($engine)
          return true;
        elseif ($engine === null)
          return null;
        else
          return false;
    }
    
    private function is_view($table)
    {
        $is_table = $this->is_table($table);
        
        if ($is_table === false)
          return true;
        elseif ($is_table === null)
          return null;
        else
          return false;
    }
    
    private function update_table_status($timeout = 300, &$error = null, &$sql = null)
    {
        $error = null;
        $sql = null;
        
        if (($this->table_status_timestamp + ($timeout * 1000)) < round((microtime(true) * 1000)))
          return $this->table_status($error, $sql);
          
        return null;
    }
    
    private function table_status(&$error = null, &$sql = null)
    {
        $error = null;
        $sql = null;
        
        $this->check_init();
        
        $sql = "SHOW TABLE STATUS";
    
        if (!$result = $this->db->query($sql))
        {
          $error = $this->db->error;
          
          return false;
        }
        
        if (!$result->num_rows)
        {
          $error = "No status response";
          
          return false;
        }
        
        $this->table_status = new \stdClass;
        
        while ($obj = $result->fetch_object())
        {
          $name = $obj->Name;
          
          unset($obj->Name);
          
          $this->table_status->$name = $obj;
        }
        
        $this->table_status_timestamp = round((microtime(true) * 1000));
        
        return true;    
    }
    
    private function fetch_object(\mysqli_result $result, $classname = null, $args = null)
    {
        if (($classname) && (is_array($args)))
          return $result->fetch_object($classname, $args);
        else
          return $result->fetch_object();
    }
    
    public function commit($name, $flags = 0)
    {
        if (!isset($this->transactions[$name]))
          return null;

        $res = $this->db->commit($flags, $name);

        unset($this->transaction[$name]);

        return $res;
    }
    
    public function rollback($name, $flags = 0)
    {
        if (!isset($this->transactions[$name]))
          return null;

        $res = $this->db->rollback($flags, $name);

        unset($this->transaction[$name]);

        return $res;
    }
    
    public function begin_transaction($name, $flags = MYSQLI_TRANS_START_WITH_CONSISTENT_SNAPSHOT)
    {
        if (isset($this->transactions[$name]))
          return null;

        if ($this->db->begin_transaction($flags, $name))
        {
          $this->transactions[$name] = $this->get_timestamp();

          return true;
        }
        
        return false;
    }
    
    public function get_by_timestamp_represent($table, $condition = "'1'", $desc = false, &$no_result = null, &$error = null, &$sql = null)
    {	
        $error = null;
        $sql = null;
        $results = null;
        
        $no_result = false;
        
        $nulldate = "1970-01-01";
        $nulltime = "00:00:00";
        
        switch ($table)
        {
          case "datasets":
          case "testresults":
            break;
          default:
            return $nulldate." ".$nulltime;
        }
        
        $date_rep = $this->select($table, "timestamp_represent", $condition." GROUP BY timestamp_represent ORDER BY date_rep ".(($desc) ? "DESC" : "ASC")." LIMIT 1", null, true, true, false, $results, $error, $sql);
        
        if (!$date_rep)
        {
          $no_result = true;
          $date_rep = $nulldate;
        }
          
        return $date_rep." ".$nulltime;
    }
    
    public function get_earliest($table, $condition = "'1'", &$no_result = null, &$error = null, &$sql = null)
    {
        return $this->get_by_timestamp_represent($table, $condition, false, $no_result, $error, $sql);
    }
    
    public function get_latest($table, $condition = "'1'", &$no_result = null, &$error = null, &$sql = null)
    {
        return $this->get_by_timestamp_represent($table, $condition, true, $no_result, $error, $sql);
    }
    
    public function select($table, $field = "*", $conditions = "'1'", $callbacks_execute = null, $single_select = false, $force_stdclass = false, $force_is_view = false, &$result_count = null, &$error = null, &$sql = null)
    {
        $error = null;
        $sql = null;
        $result_count = -1;
        
        $this->check_init();
        $this->update_table_status();
        
        $sql = "SELECT ";
        
        if (is_array($field))
        {
          $add = "";
          
          foreach ($field as $func => $ident)
          {
            if (is_numeric($func))
              $add .= ",`".$ident."`";
            else
              $add .= ",".$func." as `".$ident."`";
          }
          
          $sql .= substr($add, 1);
        }
        elseif (($field == "*") || ($field === null))
        {
          $sql .= "* ";
        }
        else
        {
          $sql .= "`".$field."` ";
        }
        
        $sql .= "FROM ";
        $tableclass = ucfirst($table);
        
        if (is_array($table))
        {
          $add = "";
          
          foreach ($table as $name => $alias)
          {
            $add .= ",`".$name."`";
            
            if ($alias)
              $add .= " as `".$alias."`";
          }
          
          $sql .= substr($add, 1);
          $force_stdclass = true;
        }
        else
        {
          $sql .= "`".$table."` ";
          
          if ($this->is_view($tableclass))
            $force_is_view = true;
        }
        
        $sql .= " WHERE ".$conditions;

        $result = $this->db->query($sql);
        
        if (!$result)
        {
          $error = $this->db->error;
          
          return null;
        }
        
        $result_count = $result->num_rows;
        
        if ($result_count == 0)
        {
          $error = "No match";
          
          return null;
        }
        
        $sqlclass = (($force_stdclass) ? null : ((class_exists("WelterRocks\\CoronaData\\Table\\".$tableclass)) ? "WelterRocks\\CoronaData\\Table\\".$tableclass : null));
        
        if ($single_select)
        {
          $obj = $this->fetch_object($result, $sqlclass, (($sqlclass) ? array($this->db, $force_is_view) : null));
            
          $result->free();

          if ((is_array($field)) || ($field == "*") || ($field === null))
            return $obj;
                  
          if (!isset($obj->$field))
          {
            $error = "Field not found";
            
            return null;
          }
        
          return $obj->$field;
        }
        
        $results = array();
                
        while ($obj = $this->fetch_object($result, $sqlclass, (($sqlclass) ? array($this->db, $force_is_view) : null)))
        {
          if ($callbacks_execute)
          {
            $retval = new \stdClass;
            $retval->uid = $obj->uid;
            
            if ((!is_array($callbacks_execute)) && (!is_object($callbacks_execute)))
              $callbacks_execute = array($callbacks_execute);

            $retval->callback_count = 0;
            
            foreach ($callbacks_execute as $callback => $args)
            {
              $retval->callback_count++;
              
              try
              {
                if (method_exists($obj, $callback))
                {
                  if (!is_array($args))
                    $args = array($args);
                    
                  $retval->result = call_user_func_array(array($obj, $callback), $args);
                }
                else
                {
                  $retval->error = "Method '".$calback."' not found";
                }
              }
              catch (Exception $ex)
              {
                $retval->exception = $ex;
              }
              catch (\exception $ex)
              {
                $retval->general_exception = $ex;
              }
              
              array_push($results, $retval);
            }
          }
          else
          {
            array_push($results, $obj);
          }
        }
          
        $result->free();
          
        return $results;
    }
    
    public function get_object($object_name, object $data, &$error = null, &$sql = null)
    {
        $resultcount = null;
        $conditions = "";
        
        foreach ($data as $key => $val)
        {
          if ((is_array($val)) && (count($val) == 1))
            $conditions .= " AND `".$key."` = ".$val[0];
          elseif (is_object($val))
            die(print_r($val,1));
          else
            $conditions .= " AND `".$key."` = '".$this->esc($val)."'";
        }
        
        return $this->select($object_name, "*", substr($conditions, 5), null, true, false, false, $resultcount, $error, $sql);
    }
    
    public function register_object($object_name, object $data, $select_required_fields_only = false, $disable_autoexec = false, $zero_as_null = false, &$error = null, &$sql = null)
    {
        $error = null;
        $sql = null;
        
        $this->check_init();
        $this->update_table_status();

        $table = "WelterRocks\\CoronaData\\Table\\".$object_name;
        
        if (!class_exists($table))
          throw new Exception("Class '".$object_name."' not found");
          
        if (!$this->is_table(strtolower($object_name)))
          throw new Exception("Not an installed table '".$object_name."'");
        
        $obj = new $table($this->db);
        
        if ($disable_autoexec)
          $obj->disable_autoexec(true);
        
        foreach ($data as $key => $val)
          $obj->$key = $val;
                  
        return $obj->insert(null, false, null, "self", $select_required_fields_only, $zero_as_null, $error, $sql);
    }
    
    public function install($force_install = false, &$errors = null, &$sqls = null)
    {
        return $this->maintain("install", $force_install, $errors, $sqls);
    }
    
    public function analyze(&$errors = null, &$sqls = null)
    {
        return $this->maintain("analyze", null, $errors, $sqls);
    }
    
    public function optimize(&$errors = null, &$sqls = null)
    {
        return $this->maintain("optimize", null, $errors, $sqls);
    }
    
    public function maintain($func, $force = false, &$errors = null, &$sqls = null)
    {
        $errors = array();
        $sqls = array();
        
        switch ($func)
        {
          case "install":
          case "analyze":
          case "optimize":
            break;
          default:
            return null;
        }
        
        $tables = array(
          "Locations" => true,
          "Datasets" => true,
          "Testresults" => true,
          "Nowcasts" => true,
          "Grafana" => false
        );
        
        $errorcount = 0;
        
        foreach ($tables as $table => $is_view)
        {
          $error = null;
          $sql = null;
          
          $newtable = "WelterRocks\\CoronaData\\Table\\".$table;
          
          $obj = new $newtable($this->db);
                          
          if (!$obj->$func($force, $error, $sql))
            $errorcount++;
          
          $errors[$table] = $error;
          $sqls[$table] = $sql;
        }
        
        return (($errorcount) ? false : true);
    }
        
    public function get_table_status()
    {
        $this->update_table_status();
        
        return $this->table_status;    
    }
    
    public function init()
    {
        if (is_object($this->db))
            throw new Exception("Already initialized");
            
        $this->db = new \mysqli(
            $this->config->mysql_hostname ?: "localhost",
            $this->config->mysql_username ?: "root",
            $this->config->mysql_password ?: null,
            $this->config->mysql_database ?: "corona",
            $this->config->mysql_hostport ?: 3306,
            $this->config->mysql_socket ?: null
        );
                        
        if ($this->db->ping())
          return $this->table_status();
          
        return false;
    }
    
    public function destroy()
    {
        $this->db = null;
        $this->config = null;
        $this->config_file = null;
    }

    function __construct($config = ".coronadatarc", $hostname = null, $port = null, $username = null, $password = null, $database = null, $socket = null)
    {
        $this->config_file = $config;

        $this->config = new Config($this->config_file);

        if ($hostname)
            $this->config->mysql_hostname = $hostname;
        if ($port)
            $this->config->mysql_hostport = $port;
        if ($username)
            $this->config->mysql_username = $username;
        if ($password)
            $this->config->mysql_password = $password;
        if ($database)
            $this->config->mysql_database = $database;
        if ($socket)
            $this->config->mysql_socket = $socket;

        if (($username) || ($password) || ($hostname) || ($port) || ($database) || ($socket))
            $this->config->write($config);
    }
        
    function __destruct()
    {
        $this->destroy();
    }
}
