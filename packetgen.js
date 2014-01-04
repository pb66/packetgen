
var packetgen = {

  apikey: false,

  'get':function()
  {
    var apikey_str = ""; if (this.apikey) apikey_str = "?apikey="+this.apikey;
    var result = {};
    $.ajax({ url: path+"packetgen/get.json"+apikey_str, dataType: 'json', async: false, success: function(data) {result = data;} });
    return result;
  },
  
  'getinterval':function()
  {
    var apikey_str = ""; if (this.apikey) apikey_str = "?apikey="+this.apikey;
    var result = {};
    $.ajax({ url: path+"packetgen/getinterval.json"+apikey_str, dataType: 'json', async: false, success: function(data) {result = data;} });
    return result;
  },

  'set':function(packet,interval)
  {
    var apikey_str = ""; if (this.apikey) apikey_str = "?apikey="+this.apikey;
    var result = {};
    $.ajax({ url: path+"packetgen/set.json"+apikey_str, data: "packet="+JSON.stringify(packet)+"&interval="+interval, async: false, success: function(data){} });
    return result;
  },
  
  'get_raspberrypi_settings':function()
  {
    var apikey_str = ""; if (this.apikey) apikey_str = "?apikey="+this.apikey;
    console.log("here");
    var result = {};
    $.ajax({ url: path+"raspberrypi/get.json"+apikey_str, dataType: 'json', async: false, success: function(data) {result = data;} });
    return result;
  }
}
