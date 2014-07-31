<?php
/*

  All Emoncms code is released under the GNU Affero General Public License.
  See COPYRIGHT.txt and LICENSE.txt.

  ---------------------------------------------------------------------
  Emoncms - open source energy visualisation
  Part of the OpenEnergyMonitor project:
  http://openenergymonitor.org

*/
// no direct access
defined('EMONCMS_EXEC') or die('Restricted access');

function packetgen_controller()
{
  global $mysqli,$redis,$session, $route;
  $result = false;

  include "Modules/packetgen/packetgen_model.php";
  $packetgen = new PacketGen($mysqli,$redis);
  
  if ($route->format == 'html')
  {
    if ($route->action == 'view')
    {
      $result = view("Modules/packetgen/packetgen_view.php",array());
    }
  }

  if ($route->format == 'json')
  {
    if ($route->action == 'get' && $session['read']) {
      $result = $packetgen->get($session['userid']);
    }
    
    if ($route->action == 'getinterval' && $session['read']) {
      $result = $packetgen->get_interval($session['userid']);
    }
    
    if ($route->action == 'set' && $session['write'])
    {
      $result = $packetgen->set($session['userid'],get('packet'),get('interval'));
    }
    
    if ($route->action == 'update' && $session['write'])
    {
      $result = $packetgen->update($session['userid'],get('id'),get('value'));
    }
    
    if ($route->action == 'rfpacket' && $session['read']) {
      $result = $packetgen->getrfm12packet($session['userid']);
    }
  }

  return array('content'=>$result);
}
