function loadedKeys(myData) {
	console.log("loadedKeys");
	var firstkeyid = -1;
	$(myData).find("store[id=4]").find("keys:last").find("key").each(function(i) {
	  var key = $(this);		
		var keyid = key.attr("id");
		if (firstkeyid == -1) firstkeyid = keyid;		
		var keyname = key.attr("name"); 	  
		if (keyname.substr(0, 6) != "Record")
				$("<option value=\"" + keyid + "\">" + keyname + "</option>").appendTo("#key");
	});	
	
	$.ajax({
	  url: '/xml/word-voc?p=' + firstkeyid,
	  success: loadedKeyVals
	});
}

function loadedKeyVals(myData) {
  console.log("loadedKeyVals");
	$("#value > option").remove();
	$(myData).find("word").each(function(i) {
	  var wordStr = $(this).attr("str");
		$("<option value=\"" + wordStr + "\">" + wordStr + "</option>").appendTo("#value");
	});
}

function loadedCurrentState(myData) {
  console.log("loadedCurrentState");
	
	// parse XML & create markers & create info windows
  $(myData).find("node").each(function() {
    var node = $(this);
		var nodeName = node.attr("name");
		// parse sensors
		$(node).find("sensor").each(function() {
			var sensor = $(this);
			var sensorid = sensor.attr("id");
			var keyname = "FTR_" + sensor.attr("featureofmeasurement").toUpperCase();
			$("<option value=\"" + sensorid + "\">" + nodeName + "/" + getTranslation(keyname) + "</option>").appendTo("#ch-sensor");
		});
	});
}

// generating the rule
var conditionOr = new Array();
var conditionKey = new Array();
var conditionOp = new Array();
var conditionVal = new Array();
var currentCondition = 1;

conditionOr[0] = false;
conditionKey[0] = "$from";
conditionOp[0] = "$eq";
conditionVal[0] = "SensorAggregate";

function generateJSONRule() {
  var jsonStr = "{";
	var orBlock = false;
	
  for (i = 0; i < currentCondition; i++) {
	   // new OR block
	   if (conditionOr[i] == true && orBlock == false) {
		   jsonStr += "\"$or\":[";
		 }

		 if (conditionOr[i] == true) jsonStr += "{";
		 
	   jsonStr += "\"" + conditionKey[i] + "\":";
		 if (conditionOp[i] == "$eq") {
		   jsonStr += "\"" + conditionVal[i] + "\"";
		 } else {
		   jsonStr += "{\"" + conditionOp[i] + "\":\"" + conditionVal[i] + "\"}";
		 }
		 
		 if (conditionOr[i] == true) jsonStr += "}";
		 
		 orBlock = conditionOr[i];
		 
		 // finish OR block
		 if (i < currentCondition && conditionOr[i + 1] == false && orBlock == true) {
		   jsonStr += "]";
		 }
		 
		 if (i != currentCondition - 1) jsonStr += ", ";		 		 
	}
	
	// end last OR block if exists
  if (orBlock == true) {
    jsonStr += "]";
	}
	
	jsonStr += "}";
	return jsonStr;
}

function deleteLastCondition() {
  if (currentCondition > 1) currentCondition--;
	$("#div-conditions").html(generateJSONRule());
}

function deleteAllConditions() {
  currentCondition = 1;
	$("#div-conditions").html(generateJSONRule());
}

function addCondition() {
  conditionOr[currentCondition] = $("#or").attr("checked");
  conditionKey[currentCondition] = $("#key option:selected").text();
	conditionOp[currentCondition] = $("#op option:selected").val();
	conditionVal[currentCondition] = $("#value option:selected").text();
	currentCondition++;
	
  $("#div-conditions").html(generateJSONRule());
}

function loadedQuery(myData) {
  console.log("loadedQuery");
	console.log(myData);
	var HTML = "<h2>" + getTranslation("RESULTS") + "</h2>";
	
	$(myData).find("record").each(function() {
	  var record = $(this);
		var sensorid;
		var eventtime;
		$(record).find("field[name='AggregateSensorUId']").each(function() {
		  sensorid = $(this).attr("text");
		});
		$(record).find("field[name='AggregateTime']").each(function() {
		  eventtime = $(this).attr("text");
		});
		HTML += eventtime + " @ sensor " + sensorid + "<br>";
	});
	
	$("#div-results").html(HTML);
}

function executeQuery() {
  var queryStr = generateJSONRule();
	var url = "/proxy.php?cmd=op-search&p=q=" + escape(queryStr);
	console.log(url);
	$.ajax({
	  url: url,
		success: loadedQuery
	});
}

function xml_to_string(xml_node)  {
  if (xml_node.xml)
    return xml_node.xml;
  else if (XMLSerializer) {
    var xml_serializer = new XMLSerializer();
    return xml_serializer.serializeToString(xml_node);
  } else {
    alert("ERROR: Extremely old browser");
    return "";
  }
}

function getRuleML() {
  var queryStr = generateJSONRule();	
	var eventStr = $("#eventname").val();
	
	var url = "/proxy.php?cmd=ruleml&p=event=" + escape(eventStr) + "|q=" + escape(queryStr); 
	console.log(eventStr);
	window.open(url);
}

function getRDFData() {
  var queryStr = generateJSONRule();	
	var url = "/proxy.php?cmd=rdf&p=json=" + escape(queryStr);
	window.open(url);	
}

$("#key").live('change', function() {
   console.log($(this).val());
	 $.ajax({
	   url: '/xml/word-voc?p=' + $(this).val(),
		 success: loadedKeyVals
	 });
});	

function selectDate(date) {
  $("#ch-date").text(date);
}

var eventMarker = new Array();

function showOnMap(id, nodeName, lat, lng) {
	var markerLatLng = new google.maps.LatLng(lat, lng);
	
	// create marker	
	if (eventMarker[id] == null) {	  	
		eventMarker[id] = new google.maps.Marker({	
  	  position: markerLatLng,
  	  title: nodeName,
			icon: "http://labs.google.com/ridefinder/images/mm_20_green.png"
  	});		
	}
  	
	if (eventMarker[id].getMap() != null) eventMarker[id].setMap(null);
	else eventMarker[id].setMap(map);
}

function loadedEvents(myData) {
  console.log("loadedEvents");
	var HTML = "<h2>" + getTranslation("EVENTS") + "</h2><ul>";
	
	$(myData).find("event").each(function() {
	  var event = $(this);
		var eventTime = event.attr("timestamp");
		var eventDate = eventTime.substr(0, 10);
		HTML += "<li><a href=\"javascript:selectDate('" + eventDate + "');\">" + event.attr("name") + "</a>";
		HTML += " [ <a href=\"javascript:showOnMap(" + event.attr("id") + ", '" + escape(event.attr("name")) + "', " + event.attr("latitude") + ", " + event.attr("longitude") + ");\">" + getTranslation("SHOW") + "</a> ]<br>";
		HTML += "<font style=\"font-size: 10px\">" + eventTime + "</font></li>";
	});
	
	HTML += "</ul>";
	
	$("#div-events").html(HTML);
}

$(function() {
  // initialize rule box
	$.ajax({
	  url: '/xml/stores',
		success: loadedKeys
	});
	
	$.ajax({
	  url: '/xml/current-state',
		success: loadedCurrentState
	});
	
	$.ajax({
	  url: '/xml/get-events',
		success: loadedEvents
	});
});