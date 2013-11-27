<?php

  $domain = "messages";
  bindtextdomain($domain, "Modules/feed/locale");
  bind_textdomain_codeset($domain, 'UTF-8');

  $menu_dropdown[] = array('name'=> dgettext($domain, "RFM12b Packet Generator"), 'path'=>"packetgen/view" , 'session'=>"write", 'order' => 0 );

?>
