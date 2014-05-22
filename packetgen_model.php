<?php

/*

  Note:

  The following model implementation
  provided the quickest way of getting
  a concept up an running and may be good
  to look at in detail as requirements 
  become clearer as control develops.
  
*/

defined('EMONCMS_EXEC') or die('Restricted access');

class PacketGen
{
  private $mysqli;
  private $redis;
  private $mqtt;

  public function __construct($mysqli,$redis)
  {
    $this->mysqli = $mysqli;
    $this->redis = $redis;
  }

  public function set($userid,$packet,$interval)
  {
    // Sanitisation
    $userid = (int) $userid;
    $interval = (int) $interval;
    
    $packet = json_decode($packet);
    $checkedpacket = array();
    foreach ($packet as $variable)
    {
      if ($variable->type==0) {
        $variable->value = (bool) $variable->value;
      } elseif ($variable->type==1) {
        $variable->value = (int) $variable->value;
      } elseif ($variable->type==2) {
        $variable->value = (int) $variable->value;
        // Limit to byte value
        if ($variable->value>256) $variable->value = 256;
        if ($variable->value<0) $variable->value = 0;
        
      } else {
        $variable->value = 0;
      }
      
      $checkedvariable = new stdClass();
      $checkedvariable->name = preg_replace('/[^\w\s-_]/','',$variable->name);
      $checkedvariable->type = (int) $variable->type;
      $checkedvariable->value = $variable->value;
      $checkedpacket[] = $checkedvariable;
    }
    
    $this->mqtt_broadcast($checkedpacket);
      
    $packet = json_encode($checkedpacket);
    
    $result = $this->mysqli->query("SELECT * FROM packetgen WHERE `userid` = '$userid'");
    $row = $result->fetch_array();
    
    if ($row) {
      $this->mysqli->query("UPDATE packetgen SET `packet` = '$packet', `interval` = '$interval' WHERE `userid` = '$userid'");
      return "packet updated";
    } else {
      $this->mysqli->query("INSERT INTO packetgen (`userid`,`packet`,`interval`) VALUES ('$userid','$packet','$interval')");
      return "packet added";
    }
   
  }
  
  public function get($userid)
  {
    $userid = (int) $userid;
    $result = $this->mysqli->query("SELECT packet FROM packetgen WHERE `userid` = '$userid'");
    $row = $result->fetch_array();
    $packet = json_decode($row['packet']);
    
    if ($packet) {
      // Add special variable values for time
      foreach ($packet as $variable)
      {
        if ($variable->name=='hour') $variable->value = date('H');
        if ($variable->name=='minute') $variable->value = date('i');
        if ($variable->name=='second') $variable->value = date('s');
      }
    }
    
    return $packet;
  }
  
  public function get_interval($userid)
  {
    $userid = (int) $userid;
    $result = $this->mysqli->query("SELECT `interval` FROM packetgen WHERE `userid` = '$userid'");
    $row = $result->fetch_array();
    return $row['interval'];
  }

  public function getrfm12packet($userid)
  {
    $userid = (int) $userid;
    $result = $this->mysqli->query("SELECT packet FROM packetgen WHERE `userid` = '$userid'");
    $row = $result->fetch_array();
    $packet = json_decode($row['packet']);
    
    $str = "";
    if (!$packet) return false;
    
    foreach ($packet as $variable)
    {
      // special variable values
      if ($variable->name=='hour') $variable->value = date('H');
      if ($variable->name=='minute') $variable->value = date('i');
      if ($variable->name=='second') $variable->value = date('s');
           
      $val = $variable->value;
      
      if ($variable->type==0)
      {
        $str .= intval($val).","; 
      }
      
      if ($variable->type==1)
      {
        $p2 = $val >> 8;
        $p1 = $val - ($p2<<8);
        $str .= $p1.",".$p2.",";  
      }
      
      if ($variable->type==2)
      {
        if ($val>256) $val = 256;
        if ($val<0) $val = 0;
        $str .= $val.","; 
      }
      
    }
    
    return $str;
  }
  
  public function mqtt_broadcast($packet)
  {
    $bytes = array(); $bi=0;
    if (!$packet) return false;
    
    foreach ($packet as $variable)
    {
      // special variable values
      if ($variable->name=='hour') $variable->value = date('H');
      if ($variable->name=='minute') $variable->value = date('i');
      if ($variable->name=='second') $variable->value = date('s');
           
      $val = $variable->value;
      
      if ($variable->type==0)
      {
        $bytes[$bi] = (int) $val; $bi++;
        
      }
      
      if ($variable->type==1)
      {
        $p2 = $val >> 8;
        $p1 = $val - ($p2<<8);
        $bytes[$bi] = (int) $p1; $bi++;
        $bytes[$bi] = (int) $p2; $bi++;
      }
      
      if ($variable->type==2)
      {
        if ($val>256) $val = 256;
        if ($val<0) $val = 0;
        $bytes[$bi] = (int) $val; $bi++;
      }
      
    }
    

    if(!class_exists('SAMConnection')){
      error_reporting(E_ALL ^ (E_NOTICE | E_WARNING)); 
      require('SAM/php_sam.php');
      $this->mqtt = new SAMConnection();
      $this->mqtt->connect(SAM_MQTT, array(SAM_HOST => '127.0.0.1', SAM_PORT => 1883));
    }
      $msg_rawserial = new SAMMessage(json_encode($bytes));
      $this->mqtt->send('topic://nodetx', $msg_rawserial);
  }
}
