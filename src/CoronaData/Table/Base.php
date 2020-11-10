<?php namespace WelterRocks\CoronaData\Table;

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

use WelterRocks\CoronaData\Exception;
use WelterRocks\CoronaData\Database;

abstract class Base
{
    private $__db = null;
    private $__tablename = null;
    private $__initialized = null;

    private $__transactions = null;
    private $__autoselect_keys = null;

    private $__unknown_fields = null;
    private $__updated_fields = null;
    private $__update_version = null;
    private $__has_updates = null;    
    
    protected $__required_fields = null;
    protected $__autoexec_disabled = null;

    protected $uid = null;
    protected $timestamp_last_update = null;
    protected $timestamp_registration = null;
    protected $timestamp_deleted = null;
    protected $timestamp_undeleted = null;
    protected $timestamp_disabled = null;
    protected $timestamp_enabled = null;
    protected $update_count = null;
    protected $flag_updated = null;
    protected $flag_disabled = null;
    protected $flag_deleted = null;
    
    private function view_exception($code = 0, $ex = null)
    {
        throw new Exception("You cannot do any write action on a view", $code, $ex);

        return null;
    }
    
    abstract protected function get_install_sql();
    
    protected function check_view_and_uid($exception_suffix = null)
    {
        if ($this->is_view())
          return $this->view_exception();

        if (!$this->uid)
          throw new Exception("Missing UID".$exception_suffix);
          
        return true;
    }
    
    protected function update_clause($update = null, $zero_as_null = false)
    {
        $update_clause = "";
        $worker_count = 0;
        
        if ((is_array($update)) && (count($update) > 0))
        {
          $update_fields = $update;
        }
        elseif (is_object($update))
        {
          $update_fields = clone $update;
        }
        elseif ($update === null)
        {
          $update_fields = new \stdClass;
          
          foreach ($this as $key => $val)
          {
            if (substr($key, 0, 2) == "__")
              continue;
              
            $update_fields->$key = $val;
          }
        }
        
        foreach ($update_fields as $key => $val)
        {
            switch ($key)
            {
              case "uid":
              case "timestamp_registration":
              case "timestamp_last_update":
              case "flag_deleted":
                continue(2);
              default:
                break;
            }
            
            if (is_array($val))
            {
              if (count($val) == 1)
              {
                $update_clause .= ", `".$key."` = ".$val[0];
                $worker_count++;
              }
            }
            elseif (($val !== null) && ($val != ""))
            {
              if (($val == 0) && ($zero_as_null))
                $update_clause .= ", `".$key."` = NULL";
              elseif ((($val === 0) || ($val === false)) && ($val !== null))
                $update_clause .= ", `".$key."` = ".$val;
              elseif (is_numeric($val))
                $update_clause .= ", `".$key."` = ".$val;
              else
                $update_clause .= ", `".$key."` = '".$this->esc($val)."'";
                
              $worker_count++;
            }
        }
        
        if ($worker_count == 0)
          return null;
        
        $update_clause = "UPDATE".substr($update_clause, 1);

        return $update_clause;    
    }
    
    protected function on_duplicate_key($data = null, $zero_as_null = false)
    {
        $on_duplicate_key = "";
        
        if ($data == "ignore")
        {
          $on_duplicate_key = " ON DUPLICATE KEY UPDATE timestamp_last_update = timestamp_last_update";
        }
        elseif (((is_array($data)) && (count($data) > 0)) || (is_object($data)) || ($data === "self"))
        {
          $update_clause = $this->update_clause((($data == "self") ? null : $data), $zero_as_null);
          
          $on_duplicate_key = (($update_clause === null) ? "" : " ON DUPLICATE KEY ".$update_clause);
        }
        
        return $on_duplicate_key;
    }
    
    protected function esc($str)
    {
        return $this->__db->escape_string($str);
    }
    
    protected function get_sql_timestamp($ts = null)
    {
        return date("Y-m-d H:i:s", (($ts) ? $ts : time()));
    }
    
    protected function get_timestamp($resolution = 1000)
    {
        return round((microtime(true) * $resolution));
    }
    
    protected function add_required_field($key, $non_removable = false)
    {
        if (!isset($this->__required_fields[$key]))
        {
          $this->__required_fields[$key] = (($non_removable === false) ? false : true);
          
          return true;
        }
        elseif (($this->__required_fields[$key] === false) && ($non_removable !== false))
        {
          $this->__required_fields[$key] = true;
        }
        
        return false;
    }
    
    protected function remove_required_field($key)
    {
        if (!isset($this->__required_fields[$key]))
          return false;
          
        if ($this->__required_fields[$key] === true)
          return false;
          
        unset($this->__required_fields[$key]);
        
        return true;
    }
    
    protected function is_required_field($key)
    {
        if (!isset($this->__required_fields[$key]))
          return false;
        
        return true;
    }
    
    protected function skip_field($field, $ignore_timestamp_last_update = false)
    {
        switch ($field)
        {
          case "timestamp_last_update":
            if ($ignore_timestamp_last_update)
              return false;
          case "uid":
          case "timestamp_registration":
          case "timestamp_deleted":
          case "timestamp_undeleted":
          case "timestamp_disabled":
          case "timestamp_enabled":
          case "update_count":
          case "flag_updated":
          case "flag_disabled":
          case "flag_deleted":
            return true;
          default:
            return false;
        }
    }
    
    protected function get_db()
    {
        return $this->__db;
    }
    
    public function get_required_fields()
    {
        return $this->__required_fields;
    }
    
    public function get_tablename()
    {
        return $this->__tablename;
    }
    
    public function begin_transaction($name, $flags = MYSQLI_TRANS_START_WITH_CONSISTENT_SNAPSHOT)
    {
        if ($this->is_view())
          return $this->view_exception();
          
        if (isset($this->__transactions[$name]))
          return null;
        
        if ($this->__db->begin_transaction($flags, $name))
        {
          $this->__transactions[$name] = $this->get_timestamp();
          
          return true;
        }
        
        return false;
    }
    
    public function commit($name, $flags = 0)
    {
        if ($this->is_view())
          return $this->view_exception();
        
        if (!isset($this->__transactions[$name]))
          return null;
        
        $res = $this->__db->commit($flags, $name);
        
        unset($this->__transaction[$name]);
        
        return $res;
    }
    
    public function rollback($name, $flags = 0)
    {
        if ($this->is_view())
          return $this->view_exception();
        
        if (!isset($this->__transactions[$name]))
          return null;
        
        $res = $this->__db->rollback($flags, $name);
        
        unset($this->__transaction[$name]);
        
        return $res;
    }
    
    public function clear_records()
    {
        if ($this->is_view())
          return $this->view_exception();
          
        return $this->__db->query("TRUNCATE `".$this->__tablename."`");
    }
    
    public function is_view()
    {
        if (isset($this->__is_view))
          return true;
          
        return false;
    }
    
    public function is_installed()
    {
        $result = $this->__db->query("SHOW TABLES");
        
        if ($result->num_rows)
        {
            while ($obj = $result->fetch_object())
            {
                foreach ($obj as $key => $val)
                {
                    if ($val == $this->__tablename)
                      return true;
                }
            }
            
            $result->free();
        }
        
        return false;
    }
      
    public function install($force_install = false, &$error = null, &$sql = null)
    {
        $error = null;
        $sql = null;
        
        if (!$force_install)
        {
          if ($this->is_installed())
          {
            $error = "Already installed";
            
  	    return false;
          }
        }
        
        $drop = (($force_install) ? "DROP ".(($this->is_view()) ? "VIEW" : "TABLE")." IF EXISTS `".$this->__tablename."`;" : "");

        $sql = "SET FOREIGN_KEY_CHECKS=0;\n".$drop."
        SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";
        SET AUTOCOMMIT = 0;
        START TRANSACTION;
        SET time_zone = \"+00:00\";
          
        ".$this->get_install_sql()."
          
        COMMIT;
        SET FOREIGN_KEY_CHECKS=1;
        ";

        if (!$this->__db->multi_query($sql))
        {
            $error = $this->__db->error;
            return false;
        }
               
        if ($this->__db->error)
        {
            $error = $this->__db->error;
            return false;
        }
        
        while ($this->__db->next_result()) {;}
        
        if (!$this->is_view())
        {
          if (!$this->__db->multi_query("CREATE TRIGGER IF NOT EXISTS `update_set_".$this->__tablename."` BEFORE UPDATE ON `".$this->__tablename."` FOR EACH ROW BEGIN SET NEW.update_count = OLD.update_count + 1; SET NEW.flag_updated = 1; END;"))
          {
            $error = $this->__db->error;
            return false;
          }

          while ($this->__db->next_result()) {;}
        }
              
        return true;
    }
    
    public function destroy()
    {
        $this->__db = null;
    }
    
    public function has_updates(&$update_version = null)
    {
        if ($this->__has_updates)
          $update_version = $this->get_update_version();
        else
          $update_version = -1;
        
        return $this->__has_updates;
    }
    
    public function get_update_version()
    {
        return $this->__update_version;
    }
    
    public function revert_updates($version_number = 0)
    {
        $update_version = null;
        
        if (!$this->has_updates($update_version))
          return false;
          
        if ($version_number > $update_version)
          return false;
          
        foreach ($this->__updated_fields as $key => $versions)
        {
          foreach ($versions as $version => $data)
          {
            if ($version == $version_number)
            {
              $this->$key = $data->old_value;
              
              unset($this->__updated_fields[$key][$version]);
            }
            elseif ($version > $version_number)
            {
              unset($this->__updated_fields[$key][$version]);
            }
          }
        }
        
        $this->__update_version = $version_number;
        
        if ($version_number == 0)
          $this->__has_updates = false;
        
        return true;
    }

    public function insert($prefix = null, $ignore = false, $partitions = null, $on_duplicate_key_data = null, $autoselect_fields_only = false, $zero_as_null = false, &$error = null, &$sql = null)
    {
        $error = null;
        $sql = null;
        
        if ($this->is_view())
          return $this->view_exception();
          
        if (method_exists($this, "autoexec_insert_before"))
          $this->autoexec_insert_before();
          
        foreach ($this->__required_fields as $field => $permanent)
        {
          if ($this->skip_field($field))
            continue;
                      
          if ((!isset($this->$field)) || (empty($this->$field)) || ($this->$field === null))
            throw new Exception("Missing required field '".$field."'");
        }
        
        $sql = "INSERT ";
        
        if ((is_array($partitions)) && (count($partitions) > 0))
        {
          $sql .= "PARTITION ".implode(", ", $parititions)." ";  
        }
        elseif (is_object($partitions))
        {
          $part = "";
          
          foreach ($partitions as $partition)
            $part .= ", ".$partition;
            
          $sql .= "PARTITION ".substr($part, 2);

          unset($part);
          unset($partition);
        }
        elseif ($partitions !== null)
        {
          $sql .= "PARTITION ".$partitions;
        }
        
        $no_insert_id = false;
        
        switch (strtolower($prefix))
        {
          case "delayed":
          case "low_priority":
          case "high_priority":
            $no_insert_id = true;
            $sql .= strtoupper($prefix)." ";
            break;
        }
        
        if ($ignore)
          $sql .= "IGNORE ";
        
        $sql .= "INTO `".$this->__tablename."` ";
        
        $keys = "";
        $vals = "";
        
        foreach ($this as $key => $val)
        {
          if (substr($key, 0, 2) == "__")
            continue;

          if ($this->skip_field($key))
            continue;              
          
          if (is_array($val))
          {
            if (count($val) == 1)
            {
              $keys .= ",`".$key."`";
              $vals .= ",".$val[0];
            }
          }
          elseif (($val !== null) && ($val != ""))
          {
            $keys .= ",`".$key."`";
            
            if ((($val === 0) || ($val === false)) && ($val !== null) && ($val !== ""))
              $vals .= ",".$val;
            elseif (is_numeric($val))
              $vals .= ",".$val;
            else
              $vals .= ",'".$this->esc($val)."'";
          }
        }
        
        $keys .= ",`timestamp_registration`";
        $vals .= ",'".$this->get_sql_timestamp()."'";
        $keys .= ",`timestamp_last_update`";
        $vals .= ",'".$this->get_sql_timestamp()."'";
        
        $sql .= "(".substr($keys, 1).") VALUES (".substr($vals, 1).")";

        if ($on_duplicate_key_data !== null)
            $sql .= $this->on_duplicate_key($on_duplicate_key_data, $zero_as_null);

        $retval = null;
        
        if ($insert = $this->__db->query($sql))
        {
            if ($no_insert_id)
            {
              $retval = true;
            }
            else
            {
              $this->uid = $this->__db->insert_id;
            
              $retval = $this->uid;
            }
        }
        else
        {
            $error = $this->__db->error;
        }
        
        if (method_exists($this, "autoexec_insert_after"))
          $this->autoexec_insert_after($retval);
        
        if ((!$retval) && ($insert))
        {
            if ($this->select(false, $autoselect_fields_only, $error, $sql))
              return $this->uid;
              
            return true;
        }
            
        return $retval;
    }
    
    public function save($prefix = null, $partitions = null, $autoselect_fields_only = false, $zero_as_null = false, &$error = null, &$sql = null)
    {
        $retval = $this->insert($prefix, null, $partitions, "self", $autoselect_fields_only, $zero_as_null, $error, $sql);

        return $retval;
    }
    
    public function delete(&$error = null, &$sql = null, $undelete = false)
    {
        $error = null;
        $sql = null;
        
        if ($this->is_view())
          return $this->view_exception();
          
        if (!$this->uid)
          throw new Exception("Missing UID to mark dataset as deleted");
        
        if ($this->flag_deleted)
        {
          $error = "Already deleted";
          
          return false;
        }
        
        if (method_exists($this, "autoexec_delete_before"))
          $this->autoexec_delete_before($undelete);
        
        $timestamp = $this->get_sql_timestamp();
          
        $sql = "UPDATE `".$this->__tablename."` SET `flag_deleted` = ".(($undelete) ? "1, `timestamp_" : "0, `timestamp_un")."deleted` = '".$timestamp."' WHERE `uid` = '".$this->uid."' LIMIT 1";

        if (!$this->__db->query($sql))
        {
          $error = $this->__db->error;

          return false;
        }
        
        $this->flag_deleted = (($undelete) ? 0 : 1);
        
        if ($undelete)
          $this->timestamp_undeleted = $timestamp;
        else
          $this->timestamp_deleted = $timestamp;
        
        if (method_exists($this, "autoexec_delete_after"))
          $this->autoexec_delete_before($undelete);
        
        return true;
    }
    
    public function undelete(&$error, &$sql = null)
    {
        return $this->delete($error, $sql, true);
    }
    
    public function update($no_auto_update_timestamp = false, &$applied_version = null, &$error = null, &$sql = null)
    {
        $applied_version = null;
        $error = null;
        $sql = null;
        
        if ($this->is_view())
          return $this->view_exception();
          
        if (!$this->uid)
          throw new Exception("Missing UID to update dataset");
        
        if (!$this->has_updates($applied_version))
          return false;
          
        if (method_exists($this, "autoexec_update_before"))
          $this->autoexec_update_before();
          
        $sql = "";
        
        foreach ($this as $key => $val)
        {
          if (substr($key, 0, 2) == "__")
            continue;
            
          if ($this->skip_field($key, true))
            continue;
            
          if ($key == "timestamp_last_update")
          {
            if (!$no_auto_update_timestamp)
              continue;          
          }
            
          if (is_array($val))
          {
            if (count($val) == 1)
              $sql .= ", `".$key."` = ".$val[0];
          }
          else
          {
            $sql .= ", `".$key."` = '".$this->esc($val)."'";
          }
        }
        
        $sql .= ", `flag_updated` = 1, update_count = update_count + 1";
        
        $sql = "UPDATE `".$this->__tablename."'` SET ".$update." WHERE `uid` = '".$this->uid."' LIMIT 1";
        
        if (!$this->__db->query($sql))
        {
          $error = $this->__db->error;

          return false;
        }
          
        if (method_exists($this, "autoexec_update_after"))
          $this->autoexec_update_after();

        $this->__updated_fields = array();
        $this->__update_version = 0;
        $this->__has_updates = false;
        
        return true;
    }
    
    public function apply_updates($no_auto_update_timestamp = false, &$applied_version = null, &$error = null, &$sql = null)
    {
        return $this->update($no_auto_update_timestamp, $applied_version, $error, $sql);
    }
    
    public function select($by_uid_only = false, $by_autoselected_fields_only = false, &$error = null, &$sql = null)
    {
        $error = null;
        $sql = null;
        
        $wherecount = 0;
        
        if (method_exists($this, "autoexec_select_before"))
          $this->autoexec_select_before();
        
        if ($by_uid_only)
        {
          if (!$this->uid)
            throw new Exception("Missing UID for select");
            
          $sql .= "`uid` = '".$this->uid."'";
          $wherecount++;
        }
        else
        {
          foreach ($this as $key => $val)
          {
            if (substr($key, 0, 2) == "__")
              continue;
              
            if ($by_autoselected_fields_only)
            {
              if (!$this->is_autoselect_key($key))
                continue;
            }
              
            if (($val === null) || ($val == ""))
              continue;
              
            if (is_array($val))
            {
              if (count($val) == 1)
              {
                $sql .= " AND `".$key."` = ".$val[0];
                $wherecount++;
              }
            }
            else
            {
              $sql .= " AND `".$key."` = '".$this->esc($val)."'";
              $wherecount++;
            }            
          }
        }
        
        if ($wherecount == 0)
          throw new Exception("No where clause generated. Selection aborted.");
          
        $sql = "SELECT * FROM `".$this->__tablename."` WHERE ".substr($sql, 5);
        $sql .= " LIMIT 1";
        
        if (!$result = $this->__db->query($sql))
        {
          $error = $this->__db->error;

          return null;
        }
          
        if ($result->num_rows > 1)
        {
          $result->free();
          
          throw new Exception("More than one result is not allowed by this select");
        }
        elseif ($result->num_rows == 0)
        {
          $error = "No result";
          
          return false;  
        }
        
        $obj = $result->fetch_object();
        
        if (!is_object($obj))
          throw new Exception("Fetch object did not return a valid object");
      
        foreach ($obj as $key => $val)
        {
          $this->$key = $val;
        }

        if (method_exists($this, "autoexec_select_after"))
          $this->autoexec_select_after();
        
        $this->__updated_fields = array();
        $this->__update_version = 0;
        $this->__has_updates = false;

        return true;        
    }
    
    public function reload(&$error = null, &$sql = null)
    {
        return $this->select(true, false, $error, $sql);
    }
    
    public function field_exists($field)
    {
        foreach ($this as $key => $val)
        {
          if (substr($key, 0, 2) == "__")
            continue;
            
          if ($key == $field)	
            return true;
        }
        
        return false;    
    }
    
    public function is_unknown_field($field)
    {
        foreach ($this->__unknown_fields as $key => $val)
        {
          if (substr($key, 0, 2) == "__")
            continue;
            
          if ($key == $field)	
            return true;
        }
        
        return false;    
    }
    
    public function has_unknown_fields()
    {
        return count($this->__unknown_fields);
    }
    
    public function get_autoselect_keys()
    {
        return $this->__autoselect_keys;
    }
    
    public function is_autoselect_key($key)
    {
        if (isset($this->__autoselect_keys[$key]))
          return true;
          
        return false;
    }
    
    public function maintain($func, &$error = null, &$sql = null)
    {
        switch (strtolower($func))
        {
          case "analyze":
          case "optimize":
            break;
          default:
            return false;
        }
        
        $sql = strtoupper($func)." TABLE `".$this->get_tablename()."`";
        
        $result = null;
        
        if ($res = $this->__db->query($sql))
        {
          if ($res->num_rows)
          {
            $result = array();
            
            while ($obj = $res->fetch_object())
              array_push($result, $obj);
              
            $res->free();
          }
        }
        else
        {
          $error = $this->__db->error;
        }
        
        return $result;
    }
    
    public function optimize($null = null)
    {
        return $this->maintain("optimize");
    }
    
    public function analyze($null = null)
    {
        return $this->maintain("analyze");
    }
    
    public function disable_autoexec($disable = true)
    {
        $this->__autoexec_disabled = $disable;
    }
    
    public function autoexec_is_disabled()
    {
        return $this->__autoexec_disabled;
    }
    
    function __get($key)
    {
        if ((substr($key, 0, 2) == "__") && ($key != "__is_view"))
          throw new Exception("Read-access to a super private is denied.");
        elseif ($key == "__is_view")
          return ((isset($this->__is_view)) ? $this->__is_view : null);

        if ($this->is_unknown_field($key))
          return $this->__unknown_fields[$key];
        
        return $this->$key;
    }
    
    function __set($key, $val)
    {
        switch ($key)
        {
          case "__db":
          case "__tablename":
              throw new Exception("Write-access to a super private is denied.");
              return;
          default:
//              if ($this->$key != $val)
//              {
                $this->__has_updates = true;
                
                if (!isset($this->__updated_fields[$key]))
                  $this->__updated_fields[$key] = array();
                
                $index = $this->__update_version;
                
                $this->__updated_fields[$key][$index] = new \stdClass;
                $this->__updated_fields[$key][$index]->key = $key;
                $this->__updated_fields[$key][$index]->old_value = $this->$key;
                $this->__updated_fields[$key][$index]->new_value = $val;
                $this->__updated_fields[$key][$index]->timestamp = $this->get_timestamp();
                
                $this->__update_version++;
                
                if (!$this->field_exists($key))
                {
                  $this->__unknown_fields[$key] = $val;
                }
                else
                {
                  $this->$key = $val;
                }
//              }
//              else
//              {
//                throw new Exception("Unable to set ".$key." to ".$val);
//              }
              return;
        }
    }

    function __construct(\mysqli $db, $force_is_view = false)
    {
        $this->__db = $db;
        $this->__tablename = strtolower(basename(str_replace("\\", "/", get_class($this))));
        
        $this->__transactions = array();

        $this->__unknown_fields = array();        
        $this->__updated_fields = array();        
        $this->__update_version = 0;
        $this->__has_updates = false;
        
        if ($force_is_view)
	  $this->__is_view = true;
        
        $this->__required_fields = array(
          "uid" => true,
          "timestamp_registration" => true,
          "timestamp_last_update" => true,
          "timestamp_deleted" => true,
          "timestamp_undeleted" => true,
          "timestamp_disabled" => true,
          "timestamp_enabled" => true,
          "update_count" => true,
          "flag_updated" => true,
          "flag_disabled" => true,
          "flag_deleted" => true
        );
        
        $this->__autoselect_keys = array();
        $this->__autoexec_disabled = false;
        
        foreach ($this as $key => $val)
        {
          if ($val === true)
            $this->__autoselect_keys[$key] = $val;
        }
        
        if (method_exists($this, "autoexec_on_construct"))
          $this->autoexec_on_construct();
    }
        
    function __destruct()
    {
        if (method_exists($this, "autoexec_on_destruct"))
          $this->autoexec_on_destruct();
          
        $this->destroy();
    }
}
