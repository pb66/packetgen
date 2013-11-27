<?php

$schema['packetgen'] = array(
  'userid' => array('type' => 'int(11)', 'Null'=>'NO', 'Key'=>'PRI'),
  'packet' => array('type' => 'text'),
  'interval' => array('type' => 'int(11)', 'Null'=>'NO', 'default'=>0)
);

