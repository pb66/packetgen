
var packetgen = {

  'get':function()
  {
    var result = {};
    $.ajax({ url: path+"packetgen/get.json", dataType: 'json', async: false, success: function(data) {result = data;} });
    return result;
  },
  
  'getinterval':function()
  {
    var result = {};
    $.ajax({ url: path+"packetgen/getinterval.json", dataType: 'json', async: false, success: function(data) {result = data;} });
    return result;
  },

  'set':function(packet,interval)
  {
    var result = {};
    $.ajax({ url: path+"packetgen/set.json", data: "packet="+JSON.stringify(packet)+"&interval="+interval, async: false, success: function(data){} });
    return result;
  },
  
  'get_raspberrypi_settings':function()
  {
    console.log("here");
    var result = {};
    $.ajax({ url: path+"raspberrypi/get.json", dataType: 'json', async: false, success: function(data) {result = data;} });
    return result;
  }
}
