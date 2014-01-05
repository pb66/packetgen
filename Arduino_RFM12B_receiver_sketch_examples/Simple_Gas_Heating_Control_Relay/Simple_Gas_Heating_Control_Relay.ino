// Simple relay control demo to control a central heating Boiler (In my case an old Worcester Bosch 240 with Honeywell control timer in my case)
// Hardware: emonTx V2 with JeeLabs relay module (any other ATmega328 / Arduino + relay combo could be used) and  Raspberry Pi with RFM12Pi module to send the control RF packets
// Software emoncms with RFM12B packet gen module: http://openenergymonitor.blogspot.co.uk/2013/11/adding-control-to-emoncms-rfm12b-packet.html
// By Glyn Hudson - Part of the OpenEnergyMonitor.org project
// 15th Dec 2013

// For my own needs and peace of mind this code has a fail safe to turn the heating off after 1hr (I am a frugual user of heating and don't often need more than 1hr of heating at a time)
// To protect the boiler this code also has a time delay of 5min inbetween switching to avoid the boiler being switched many times in case of system malfunction
// Watchdog timer has been added in case of microcontroller crash (rare) 

#include <JeeLib.h>	     //https://github.com/jcw/jeelib
#include <RTClib.h>          //https://github.com/jcw/RTC
RTC_Millis RTC;
#include <Wire.h>
#include <avr/wdt.h>     // Include watchdog library     

typedef struct
{
  byte blankchar;
  byte hour;
  byte minute;
  byte second;
  int target_temperature;
  int hysteresis;
  boolean heating;

} EmoncmsPayload;

EmoncmsPayload emoncms;

const int failsafe_off=(60 * 60);         //turn heating off after 1hr
const int time_between_switching=(60*5);  //after an on/off cycle this ammount of time (5min) (in seconds) must pass before another is allowed to protect boiler
const int HeatingRelayPin=6;
const int ledPin=9;
unsigned long timeOn, timeOff;
boolean before, heating;
boolean debug = 1; 


void setup ()
{
  
  RTC.begin(DateTime(__DATE__, __TIME__));
  DateTime now = RTC.now();
  DateTime future (now.get());
  
  rf12_initialize(1,RF12_433MHZ,210); // NodeID, Frequency, Group
  Serial.begin(9600);
  
  if (Serial) debug = 1; else debug=0;  
  if (debug ==1) Serial.println("heating boiler relay controller - openenergymonitor.org");
  if (debug==0) Serial.end();

  pinMode(HeatingRelayPin, OUTPUT);
  pinMode(ledPin, OUTPUT);
  digitalWrite(HeatingRelayPin, LOW);

  digitalWrite(ledPin, HIGH);
  delay(1500);
  digitalWrite(ledPin, HIGH);
  
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
      
      RTC.adjust(DateTime(2013, 1, 1, rf12_data[1], rf12_data[2], rf12_data[3]));   //set software RTC based on time received from emoncms
      DateTime now = RTC.now();
      
      if (debug==1)
      {
        Serial.print(emoncms.blankchar); Serial.print(" "); Serial.print(emoncms.hour); Serial.print(" "); Serial.print(emoncms.minute); Serial.print(" ");
        Serial.print(emoncms.second); Serial.print(" "); Serial.print(emoncms.target_temperature); Serial.print(" "); Serial.print(emoncms.hysteresis); Serial.print(" ");
        Serial.println(emoncms.heating);
        Serial.print(now.hour(), DEC); Serial.print(':'); Serial.print(now.minute(), DEC); Serial.print(':'); Serial.print(':'); Serial.print(now.second(), DEC); Serial.println();
        //Serial.print(before); Serial.print(" "); Serial.println(timeOn); Serial.println(" "); Serial.println(timeOff); Serial.println(" "); Serial.println(millis()); Serial.println(" ");
        Serial.print("Heating: "); Serial.println(heating);
        Serial.println();
       }
     
      
      if ((emoncms.heating==1) && (((now.get() - timeOff) > time_between_switching)))        //look for step change from 0 to 1 and only alow heating to turn on after it's been off for 5min to save wear on boiler if something was to go wrong
      {
        heating=1;
        timeOn = now.get();                      //record when the heading was turned on in seconds since 2000
      }
      
      if (emoncms.heating==0)
      {
        
        if (heating==1) timeOff = now.get();     //if heatiing was on before record the time it was turned off in seconds since 2000
        heating=0;
     }
  }
  }

DateTime now = RTC.now();
if ((now.get() - timeOn) > failsafe_off) heating=0;          //if heating has been on for 1hr turn it off


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
