// Simple relay control demo to control a central heating Boiler (In my case an old Worcester Bosch 240 with Honeywell control timer in my case)
// Hardware: emonTx V2 with JeeLabs relay module (any other ATmega328 / Arduino + relay combo could be used) and  Raspberry Pi with RFM12Pi module to send the control RF packets
// Software emoncms with RFM12B packet gen module: http://openenergymonitor.blogspot.co.uk/2013/11/adding-control-to-emoncms-rfm12b-packet.html

// For my own needs and peace of mind this code has a fail safe to turn the heating off after 1hr (I am a frugual user of heating and don't often need more than 1hr of heating at a time)
// To protect the boiler this code also has a time delay of 5min inbetween switching to avoid the boiler being switched many times in case of system malfunction
// Watchdog timer has been added in case of microcontroller crash (rare) 

// By Glyn Hudson - Part of the OpenEnergyMonitor.org project
// 15th Dec 2013
// Licence: GNU GPL V3


/*Recommended node ID allocation
------------------------------------------------------------------------------------------------------------
-ID-        -Node Type- 
0        - Special allocation in JeeLib RFM12 driver - reserved for OOK use
1-4     - Control nodes 
5-10        - Energy monitoring nodes
11-14        --Un-assigned --
15-16        - Base Station & logging nodes
17-30        - Environmental sensing nodes (temperature humidity etc.)
31        - Special allocation in JeeLib RFM12 driver - Node31 can communicate with nodes on any network group
-------------------------------------------------------------------------------------------------------------
*/

#include <JeeLib.h>	     //https://github.com/jcw/jeelib
#include <avr/wdt.h>     // Include watchdog library     

typedef struct
{
  boolean heating;

} EmoncmsPayload;

EmoncmsPayload emoncms;

const int failsafe_off=3600000;                   //turn heating off after 1hr
const int time_between_switching=300000;         //allow 5min inbetween switching to proect boiler 


const int HeatingRelayPin=6;
const int ledPin=9;

unsigned long timeOn;
unsigned long timeOff=time_between_switching;    //start at 5min so we dont need to wait 5min before we can turn heating on to start with
boolean before, heating;


void setup ()
{

  rf12_initialize(1,RF12_433MHZ,210);             // NodeID, Frequency, Group   - set to match RFM12Pi transmitter and your system
  
  pinMode(HeatingRelayPin, OUTPUT);
  pinMode(ledPin, OUTPUT);
  digitalWrite(HeatingRelayPin, LOW);
  
  wdt_enable(WDTO_8S);   // Enable hardware anti crash watchdog: max 8 seconds
}

void loop ()
{
  if (rf12_recvDone() && rf12_crc == 0 && (rf12_hdr & RF12_HDR_CTL) == 0)         // when RF packet has been received 
  {
    int node_id = (rf12_hdr & 0x1F);                                             // Extract transmitter node ID
    
    if (node_id == 15)                                                           // Emoncms RFM12Pi node id is set to 15
    {
      // The packet data is contained in rf12_data, the *(EmoncmsPayload*) part tells the compiler
      // what the format of the data is so that it can be copied correctly
      emoncms = *(EmoncmsPayload*) rf12_data;

      if ((emoncms.heating==1) && (before==0) && ((millis()-timeOff) > time_between_switching))        //look for step change from 0 to 1 and only alow heating to turn on after it's been off for 5min to save wear on boiler if something was to go wrong
      {
        heating=1;
        timeOn=millis();
        before=1;
      }
      
      if ((emoncms.heating==0) && ((millis()-timeOn) > time_between_switching ))   //only alow heating to turn off after it's been on for 5min to save wear on boiler if something was to go wrong
      {
        heating=0;
        before=0;
        timeOff=millis();
     }
  }
  }


if ((millis()-timeOn) > failsafe_off) heating=0;          //if heating has been on for 1hr turn it off


if (heating==0) //turn the heating off 
{
  digitalWrite(HeatingRelayPin, LOW);
  digitalWrite(ledPin, LOW);
}

if (heating==1) //turn the heating on
 {
  digitalWrite(HeatingRelayPin, HIGH);
  digitalWrite(ledPin, HIGH);
 }
  

 wdt_reset();           // Reset watchdog - this must be called every 8s - if not the ATmega328 will reboot
}
