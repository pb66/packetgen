// Simple relay control demo to control a central heating Boiler (In my case an old Worcester Bosch 240 with Honeywell control timer in my case)
// Hardware: emonTx V2 with JeeLabs relay module (any other ATmega328 / Arduino + relay combo could be used) and  Raspberry Pi with RFM12Pi module to send the control RF packets
// Software emoncms with RFM12B packet gen module: http://openenergymonitor.blogspot.co.uk/2013/11/adding-control-to-emoncms-rfm12b-packet.html
// By Glyn Hudson - Part of the OpenEnergyMonitor.org project

//Room temperature received from emonTH on node ID and PacketGen Control packets received from RFM12Pi on node 15

//Nov 2014 




#define RF69_COMPAT 0        //rfm12B
#include <JeeLib.h>	           //https://github.com/jcw/jeelib
#include <RTClib.h>             //https://github.com/jcw/rtclib - software RTC
RTC_Millis RTC; DateTime future;
#include <Wire.h>
#include <avr/wdt.h>     // Include watchdog library     

#define myNodeID 2          //node ID of Rx (range 0-30) 
#define network     210      //network group (can be in the range 1-250).
#define RF_freq RF12_433MHZ     //Freq of RF12B can be RF12_433MHZ, RF12_868MHZ or RF12_915MHZ. Match freq to module

//FAIL SAFE VARIABLES 
const int MaxTemp = 22;            //hardcoded max temperature deg C
const int MaxHeatingTime =4;   //longest period heating can be on for in hrs


const int RelayPin=6;
const int ledPin=9;
const int RFM12Pi_nodeID=15;
const int emonTH_nodeID=19;
const boolean debug=1;
int unsigned long off_time; 

boolean heating, last_heating_state,relay, PID_control;
double raw_roomTemp, roomTemp;
int Setpoint;

//emoncms RF structure
typedef struct
{
  byte glcdspace;
  byte hour;
  byte minute;
  byte second;
  int radiatorA_setpoint;
  int radiatorB_setpoint;
  int radiatorC_setpoint;
  int radiatorD_setpoint;
  boolean lightA;
  boolean lightB;
  boolean lightC;
  boolean lightD;
  boolean heating;

} EmoncmsPayload;
EmoncmsPayload emoncms;


//Living room emonTH RF structure
typedef struct {                                                      // RFM12B RF payload datastructure
      int temp;
      int temp_external;
      int humidity;    
      int battery;                                                  
} emonTHPayload;
emonTHPayload emonTH;

void setup()
{
  pinMode(ledPin,OUTPUT);
  digitalWrite(ledPin,HIGH);
  
   rf12_initialize(myNodeID,RF_freq,network);   //Initialize RFM12 with settings defined above  
  Serial.begin(9600);
  
  if (debug ==1) {
    Serial.println("heating boiler relay controller - openenergymonitor.org");
    Serial.print("Node: "); 
 Serial.print(myNodeID); 
 Serial.print(" Freq: "); 
 if (RF_freq == RF12_433MHZ) Serial.print("433Mhz");
 if (RF_freq == RF12_868MHZ) Serial.print("868Mhz");
 if (RF_freq == RF12_915MHZ) Serial.print("915Mhz");  
 Serial.print(" Network: "); 
 Serial.println(network);
  }
  if (debug==0) Serial.end();
  
 delay(3000);
 digitalWrite(ledPin,LOW);
  wdt_enable(WDTO_8S);   // Enable hardware anti crash watchdog: max 8 seconds
}

void loop()
{
   DateTime now = RTC.now();   
  
  if (rf12_recvDone() && rf12_crc == 0 && (rf12_hdr & RF12_HDR_CTL) == 0)         // when RF packet has been received 
  {
    int node_id = (rf12_hdr & 0x1F);                                         // Extract transmitter node ID
    
    if (node_id == RFM12Pi_nodeID)                                       // Emoncms RFM12Pi node id is set to 15
    {
      // The packet data is contained in rf12_data, the *(EmoncmsPayload*) part tells the compiler
      // what the format of the data is so that it can be copied correctly
      emoncms = *(EmoncmsPayload*) rf12_data;
      
      RTC.adjust(DateTime(2013, 1, 1, rf12_data[1], rf12_data[2], rf12_data[3]));   //set software RTC based on time received from emoncms
      DateTime now = RTC.now();
      
      if ( (emoncms.radiatorA_setpoint> 0) && (emoncms.radiatorA_setpoint < 300)) Setpoint = (emoncms.radiatorA_setpoint / 10.0);  //if setpoint is within limts set PID set point to match radiator A setpoint  
      
      heating = emoncms.heating; 
      
      if (debug==1)
      {
        Serial.print(emoncms.glcdspace); Serial.print(" "); Serial.print(emoncms.hour); Serial.print(" "); Serial.print(emoncms.minute); Serial.print(" ");
        Serial.print(emoncms.second); Serial.print(" "); Serial.print(emoncms.radiatorA_setpoint); Serial.print(" "); Serial.println(emoncms.heating);
        //Serial.print(now.hour(), DEC); Serial.print(':'); Serial.print(now.minute(), DEC); Serial.print(':'); Serial.print(':'); Serial.print(now.second(), DEC); Serial.println();
        //Serial.print(before); Serial.print(" "); Serial.println(timeOn); Serial.println(" "); Serial.println(timeOff); Serial.println(" "); Serial.println(millis()); Serial.println(" ");
        Serial.print("Heating: "); Serial.print(heating); Serial.print(" ");  Serial.print("Relay: "); Serial.println(relay);
        Serial.print("Now: "); Serial.print(now.get()); Serial.print(" ");  Serial.print("Off Time: "); Serial.println(off_time);
        Serial.print("Set Point: "); Serial.print(Setpoint); Serial.print(" "); Serial.print(" "); Serial.print("roomTemp"); Serial.print(roomTemp);
        Serial.println();
       }
       
    }
    
    
    if (node_id == emonTH_nodeID)   //living room emonTH temperature sensor   
    {
      emonTH = *(emonTHPayload*) rf12_data;
      raw_roomTemp = (emonTH.temp / 10);
      if ( (raw_roomTemp > 0) && (raw_roomTemp < 50)) roomTemp=raw_roomTemp;     //check temperature sensor is reporting within reasonable limits for a livingroom                                                                                                      
      if (debug==1) Serial.print("emonTH"); Serial.print(" "); Serial.println(emonTH.temp);
    }   
  } //end of RF receive 




if (relay==0) off_time = (now.get() + MaxHeatingTime * 3600L);
 last_heating_state=relay;        //record last relay heating state 
 
 if ( (heating==1) && (now.get() < off_time ) && (roomTemp < MaxTemp) && (int(roomTemp) < Setpoint)) relay =1;          //if heating is turned on and fail safe checks are passed and room temp is colder than setpoint
else relay =0;      



//if ((heating==0) && (last_heating_state==1)) 


  
 //control heating 
if (relay==0) //turn the heating off 
{
  digitalWrite(RelayPin, LOW);
  digitalWrite(ledPin, LOW);
}

if (relay==1) //turn the heating on
 {
  digitalWrite(RelayPin, HIGH);
  digitalWrite(ledPin, HIGH);
  if (last_heating_state==0) off_time = (now.get() + MaxHeatingTime * 3600L);    //record start time and calculate failsafe off-time, calculate a time which is MaxHeatingTime hrs in the future
 }    
 


wdt_reset();           // Reset watchdog - this must be called every 8s - if not the ATmega328 will reboot

}

