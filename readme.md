# RFM12b Packet Generator

Create RFM12b struct format rfm12b data packets in emoncms to be broadcast from a rfm12pi or jeelink connected to a computer or raspberrypi running emoncms. A first attempt / concept of what could be the foundations for control features in emoncms to do things like set radiator set point temperatures and so on.
 
This module works in conjunction with the raspberrypi emoncms module.

![](rfm12packetgen.png)

If your interested in control and would like to help extending this, maybe integrating buttons and sliders into emoncms dashboards which could then write to the packet generator, help would be most appreciated.

Licence: GPL GNU
Author: Trystan Lea

## API

### Updating a variable

To update a single variable in a packet use the following URL, with properties: **id** for the variable id in the packet (starts at 0), and **value** for the value you wish to assign

    http://localhost/emoncms/packetgen/update.json?id=4&value=33
    
Add the write apikey at the end with &apikey=xxxxxxxxx for external authentication.


