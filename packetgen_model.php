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
      if ($variable->type==0) {
        if ($variable->value==="true") $variable->value = true;
        if ($variable->value==="false") $variable->value = false;
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
  
  public function update($userid,$id,$value)
  {
      $userid = (int) $userid;
      $id = (int) $id;
      
      $result = $this->mysqli->query("SELECT * FROM packetgen WHERE `userid` = '$userid'");
      $row = $result->fetch_array();
      $packet = json_decode($row['packet']);
      
      if ($id<0 || $id>count($packet)-1) return false;
      
      if ($packet[$id]->type==0) {
        if ($value==="true") $value = true;
        if ($value==="false") $value = false;
        $value = (bool) $value;
      } elseif ($packet[$id]->type==1) {
        $value = (int) $value;
      } elseif ($packet[$id]->type==2) {
        $value = (int) $value;
        // Limit to byte value
        if ($value>256) $value = 256;
        if ($value<0) $value = 0;
        
      } else {
        $value = 0;
      }
      
      $packet[$id]->value = $value;
      
      if ($row) {
          $packet = json_encode($packet);
          $this->mysqli->query("UPDATE packetgen SET `packet` = '$packet' WHERE `userid` = '$userid'");
          return "packet updated";
      }
      else
      {
          return false;
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
}
