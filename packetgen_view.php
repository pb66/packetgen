<?php global $path; ?>

<script type="text/javascript" src="<?php echo $path; ?>Modules/packetgen/packetgen.js"></script>
<script type="text/javascript" src="<?php echo $path; ?>Lib/tablejs/table.js"></script>
<script type="text/javascript" src="<?php echo $path; ?>Lib/tablejs/custom-table-fields.js"></script>

<style>
#table td:nth-of-type(1) { width:50%;}
#table td:nth-of-type(2) { width:20%;}
#table td:nth-of-type(3) { width:20%;}

#table td:nth-of-type(4) { width:30px; text-align: center; }
#table td:nth-of-type(5) { width:30px; text-align: center; }
</style>

<br>

<h2>RFM12b Packet Generator</h2>
<p><b>Note:</b> variables <i>hour, minute</i> and <i>second</i> are special variable placeholders they will be assigned the current system time automatically</p>

<div class="input-prepend input-append" >
  <span class="add-on">Current packet size: <b><span id="bytesused"></span>/66 bytes</b></span>
</div>

<table class="table">
  <tbody id="table"></tbody>

  <tr>
    <td>
      <div class="input-prepend input-append">
        <span class="add-on">Add new variable: </span>
        <input type="text" id="variable-name" />
      </div>
    </td><td>
      <div class="input-prepend input-append">
        <span class="add-on">Type: </span>
        <select style="width:100px" id="variable-type">
          <option value=0>boolean</option>
          <option value=1>int</option>
          <option value=2>byte</option>
        </select>
      </div>
    </td><td>
      <div class="input-prepend input-append">
        <span class="add-on">Initial value: </span>
        <input type="text" style="width:100px" id="variable-value" />
      </div>
    </td><td>
      <button class="btn btn-primary" id="variable-add" >Add</button>
    </td>
  </tr>
</table>

<div class="input-prepend">
  <span class="add-on">Send packet every: </span>

  <select id="interval-selector" style="width:80px">
    <option value=5>5s</option>
    <option value=10>10s</option>
    <option value=30>30s</option>
    <option value=60>60s</option>
    <option value=300>5 min</option>
    <option value=600>10 min</option>
    <option value=0>Never</option>
  </select>
</div>
<br>
<button id="resetpacket" class="btn">Reload default packet</button>

<br><br>

<p><b>Structure defenition</b></p>
<p>Copy the following structure defenition on to nodes that need access to the packet variables:</p>
<pre id="structure"></pre>

<p><b>Example code</b></p>
<pre id="examplecode"></pre>

<script>
var path = "<?php echo $path; ?>";
var packet = packetgen.get();
var interval = packetgen.getinterval();
var settings = packetgen.get_raspberrypi_settings();

  var default_packet = [
    {'id':1, 'name':"glcdspace", 'type':2, 'value':0},
    {'id':2, 'name':"hour", 'type':2, 'value':0},
    {'id':3, 'name':"minute", 'type':2, 'value':0},
    {'id':4, 'name':"second", 'type':2, 'value':0},
    
    {'id':5, 'name':"radiatorA_setpoint", 'type':1, 'value':15},
    {'id':6, 'name':"radiatorB_setpoint", 'type':1, 'value':35},
    {'id':7, 'name':"radiatorC_setpoint", 'type':1, 'value':35},
    {'id':8, 'name':"radiatorD_setpoint", 'type':1, 'value':35},
    {'id':9, 'name':"lightA", 'type':0, 'value':false},
    {'id':10, 'name':"lightB", 'type':0, 'value':false},
    {'id':11, 'name':"lightC", 'type':0, 'value':false},
    {'id':12, 'name':"lightD", 'type':0, 'value':false},
    {'id':13, 'name':"heating", 'type':0, 'value':false}
  ];

console.log(packet);
console.log(interval);
if (!packet) {
  var packet = default_packet;
}

if (!interval) interval = 5;
$("#interval-selector").val(interval);

table.element = "#table";

table.fields = {
  'name':{'title':"<?php echo _('Property name'); ?>", 'type':"text"},
  'type':{'title':"<?php echo _('Type'); ?>", 'type':"select", 'options':['boolean','int','byte'], 'text-color': "#cc6600"},
  'value':{'title':"<?php echo _('Value'); ?>", 'type':"text"},

  // Actions
  'edit-action':{'title':'', 'type':"edit"},
  'delete-action':{'title':'', 'type':"delete"}
};

table.deletedata = true;
table.sortable = false;


table.data = packet;
packetgen.set(table.data,interval);

$("#resetpacket").click(function(){
  packet = default_packet;
  table.data = packet;
  packetgen.set(table.data,interval);
  
  table.draw();
});

table.draw();
$("#bytesused").html(calculate_bytes_used(table.data));
  
// Set type color to look like arduino
$("#table").bind("onDraw", function(e){ 
  $("td[field=type]").css('color',"#cc6600");
});

$("td[field=type]").css('color',"#cc6600");

$("#table").bind("onEdit", function(e){ });

$("#table").bind("onSave", function(e,id,fields_to_update){
  $("#structure").html(compile_structure(table.data));
  $("#examplecode").html(compile_examplecode(table.data,settings));
  $("#bytesused").html(calculate_bytes_used(table.data));
  
  packetgen.set(table.data,interval);
});

$("#table").bind("onDelete", function(e,id,row){ 
  $("#structure").html(compile_structure(table.data));
  $("#examplecode").html(compile_examplecode(table.data,settings));
  $("#bytesused").html(calculate_bytes_used(table.data));

  packetgen.set(table.data,interval);
});

$("#variable-add").click(function(){
  var name = $("#variable-name").val();
  var type = $("#variable-type").val();
  var value = $("#variable-value").val();
  
  table.data.push({'name':name, 'type':type, 'value':value});
  table.draw();
  
  $("#bytesused").html(calculate_bytes_used(table.data));
  
  packetgen.set(table.data,interval);
  $("#examplecode").html(compile_examplecode(packet,settings));
});

$("#structure").html(compile_structure(packet));
$("#examplecode").html(compile_examplecode(packet,settings));

$("#interval-selector").change(function()
{
  interval = $(this).val();
  packetgen.set(table.data,interval);
});

function calculate_bytes_used(variables)
{
  var size = 0;
  for (z in variables)
  {
    if (variables[z].type==0) size += 1;
    if (variables[z].type==1) size += 2;
    if (variables[z].type==2) size += 1;
  }
  return size;
}

function compile_structure(variables)
{
  var out = "typedef struct\n{\n";
  for (z in variables)
  {
    out+="  ";
    if (variables[z].type==0) out+="boolean";
    if (variables[z].type==1) out+="int";
    if (variables[z].type==2) out+="byte";
    
    out += " "+variables[z].name+";\n";
  }
  out += "\n} EmoncmsPayload;\n\nEmoncmsPayload emoncms;\n";
  return out;
}

function compile_examplecode(variables,settings)
{
  console.log(settings);
  var out = "";
  out += "#include &#60;JeeLib.h&#62;	     //https://github.com/jcw/jeelib\n\n";

  out += "typedef struct\n{\n";
  for (z in variables)
  {
    out+="  ";
    if (variables[z].type==0) out+="boolean";
    if (variables[z].type==1) out+="int";
    if (variables[z].type==2) out+="byte";
    
    out += " "+variables[z].name+";\n";
  }
  out += "\n} EmoncmsPayload;\n\nEmoncmsPayload emoncms;\n\n";

  out += "void setup ()\n";
  out += "{\n" 
  out += "  Serial.begin(9600);\n"
  out += '  Serial.println("PacketGen Reciever Example");'+"\n";
  out += "  rf12_initialize(1,";
  
  if (settings.frequency==8) out += "RF12_868MHZ";
  if (settings.frequency==4) out += "RF12_433MHZ";
  if (settings.frequency==9) out += "RF12_915MHZ";
  
  out += ","+settings.sgroup+"); // NodeID, Frequency, Group\n";
  out += "}\n\n";

  out += "void loop ()\n"; 
  out += "{\n";
  out += "  if (rf12_recvDone() && rf12_crc == 0 && (rf12_hdr & RF12_HDR_CTL) == 0)\n";
  out += "  {\n";
  out += "    int node_id = (rf12_hdr & 0x1F);\n";
    
  out += "    // Emoncms node id is set to "+settings.baseid+"\n";
  out += "    if (node_id == "+settings.baseid+")\n";        
  out += "    {\n";
  out += "      // The packet data is contained in rf12_data, the *(EmoncmsPayload*) part tells the compiler\n"; 
  out += "      // what the format of the data is so that it can be copied correctly\n";
  out += "      emoncms = *(EmoncmsPayload*) rf12_data;\n";

  for (z in variables)
  { 
    out += '      Serial.print("'+variables[z].name+': ");';
    out += " Serial.println(emoncms."+variables[z].name+");\n";
  }
  out += "      Serial.println();\n";
  out += "    }\n"
  out += "  }\n";
  out += "}\n";
  
  return out;
}

</script>
