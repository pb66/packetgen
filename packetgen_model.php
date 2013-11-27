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
      if ($variable->type==0) $variable->value = (bool) $variable->value;
      if ($variable->type==1) $variable->value = (int) $variable->value;
      if ($variable->type!=0 && $variable->type!=1) $variable->value = 0;
      $checkedpacket[] = array(
        'name'=>preg_replace('/[^\w\s-_]/','',$variable->name),
        'type'=>intval($variable->type),
        'value'=>$variable->value
      );
    }
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
      
    }
    
    return $str;
  }
}
