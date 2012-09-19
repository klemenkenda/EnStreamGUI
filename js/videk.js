// ---------------------------------------------------------------------------
// FILE: videk.js
// DESCRIPTION: Main EnStream GUI code
// DATE: 01/12/2011
// HISTORY:
// ---------------------------------------------------------------------------

// global variables
var marker;
var markers = [];
var infowindow = new google.maps.InfoWindow();
var geocoder;
var map;
var markerCluster;
var clusters = new Array();
var clusterXML;
var startupInterval;
var curLoc = 0;
var eventTime = "2012-05-28 00:00:00";


// SUPPORT FUNCTIONS ---------------------------------------------------------

function gup(name) {
  name = name.replace(/[\[]/,"\\\[").replace(/[\]]/,"\\\]");
  var regexS = "[\\?&]"+name+"=([^&#]*)";
  var regex = new RegExp( regexS );
  var results = regex.exec( window.location.href );
  if( results == null )
    return "";
  else
    return results[1];
}

function lZ(myStr) {
  outStr = myStr;
  if (myStr.length == 1) {
	  outStr = "0" + myStr;
  }
	return outStr;
}

function mysqlTimeStampToDate(timestamp) {
  var regex=/^([0-9]{2,4})-([0-1][0-9])-([0-3][0-9]) (?:([0-2][0-9]):([0-5][0-9]):([0-5][0-9]))?$/;
  var parts=timestamp.replace(regex,"$1 $2 $3 $4 $5 $6").split(' ');
  return new Date(parts[0],parts[1]-1,parts[2],parts[3],parts[4],parts[5]);
}

function addDays(myDate,days) {
 	return new Date(myDate.getTime() + days*24*60*60*1000);
}

function mysqlDateStr(myDate) {		 
  return myDate.getFullYear() + "-" + lZ((myDate.getMonth() + 1) + "") + "-" + lZ(myDate.getDate() + "");
}

function getClientWidth() {
  return document.compatMode=='CSS1Compat' && !window.opera?document.documentElement.clientWidth:document.body.clientWidth;
}
  
function getClientHeight() {
  return document.compatMode=='CSS1Compat' && !window.opera?document.documentElement.clientHeight:document.body.clientHeight;
}

// LOADED DATA FUNCTIONS -----------------------------------------------------

function handleError(data) {
  $(data).find("error").each(function() {
	  $sError = $(this);
		errorMessage = $sError.text();
		alert(errorMessage);
	}); 
}

function loadedClusters(data) {			
  // check errors
	handleError(data);
	// set global variable
	clusterXML = data;
	// start form HTML
  var HTML = "<form><select onChange='javascript:gotoCluster(this.options[this.selectedIndex].value);'>";
	HTML += "<option>" + getTranslation("CHOOSE_SITE") + "</option>";
	
	// parse XML
	var i = 0;						
	$(data).find("cluster").each(function() {
	  var cluster = $(this);
		// ignore virtual clusters
		if (cluster.attr("place") != "") {	
  		clusters[i] = new Array(3);
  		clusters[i][0] = cluster.attr("place");
  		clusters[i][1] = cluster.attr("latitude");
  		clusters[i][2] = cluster.attr("longitude");														
  		i++;
		}								
	});
		
	// create options
	for (i = 0; i < clusters.length; i++) {						
		HTML += "<option value=\"" + i + "\">" + clusters[i][0] + "</option>";
	}
	
	// finish HTML & update widget
	HTML += "</select></form>";
	$("#div-select").html(HTML);
}

function selectSensor(id) {
  $("#ch-sensor").val(id);
}

// current state
function loadedData(data) {
	// check errors
	handleError(data);			     			

	// parse XML & create markers & create info windows
  $(data).find("node").each(function() {
    var node = $(this);
		var nodeName = node.attr("name");
		var lat = parseFloat(node.attr("latitude"));
		lat += Math.random() / 5000;
		var lng = parseFloat(node.attr("longitude"));
		lng += Math.random() / 5000;
		var markerLatLng = new google.maps.LatLng(lat, lng);
		
		// create marker		
		var the_marker = new google.maps.Marker({
	    position: markerLatLng,
			title: nodeName,
			map: map,
			clickable: true
		});
							  		  	
		var iwHTML = "<b>" + getTranslation("NODE") + " (" + node.attr("id") + "): " + nodeName + "</b><br>";
			
		var tempHTML = "<br><table>";
		var measurementTime;
		var measurementDate;					
		var firstMeasurementTime;
			
		// parse virtual sensors
		var i = 0;	
		$(node).find("virtualsensor").each(function() {
			i++;
			var virtualsensor = $(this);
			var keyname = "FTR_" + virtualsensor.attr("name").toUpperCase();
				
			// TODO: debug comfort level operator in miner
			if (keyname != "FTR_COMFORT-LEVEL") {
				tempHTML += "<tr><td>" + getTranslation(keyname) + ": </td>";
				tempHTML += "<td>" + virtualsensor.attr("value") + "</td>";
					
				tempHTML += "<td></td>";
				tempHTML += "</tr>";
			}
		});
			
		// parse sensors
		i = 0;
		camera = 0;		
		$(node).find("sensor").each(function() {
		  i++;
			var sensor = $(this);
			var keyname = "FTR_" + sensor.attr("featureofmeasurement").toUpperCase(); 
				
			// measurementTime = sensor.attr("measurementtime");
			measurementTime = eventTime;												
			measurementDate = mysqlTimeStampToDate(measurementTime);
				
			if (i == 1) firstMeasurementTime = measurementTime;
				
			toDayStr = mysqlDateStr(measurementDate);
			toWeekStr = mysqlDateStr(addDays(measurementDate, -6));
			toMonthStr = mysqlDateStr(addDays(measurementDate, - measurementDate.getDate() + 1));						
			toYearStr = toDayStr;
			
			toDayOpenFuncStr = "javascript:openChart('day', " + sensor.attr("id") + ", '" + toDayStr + "');";
			toWeekOpenFuncStr = "javascript:openChart('week', " + sensor.attr("id") + ", '" + toWeekStr + "');";
			toMonthOpenFuncStr = "javascript:openChart('month', " + sensor.attr("id") + ", '" + toMonthStr + "');";
			toYearOpenFuncStr = "javascript:openChart('year', " + sensor.attr("id") + ", '" + toYearStr + "');";
			
														
		  tempHTML += "<tr><td>(" + sensor.attr("id")  + ") " + getTranslation(keyname) + ": </td>";
			// tempHTML += "<td>" + sensor.attr("lastmeasurement") + convertUnitHTML(sensor.attr("unitofmeasurement")) + "</td>";
				
			// if ((toDayStr != "NaN-NaN-NaN") && (keyname != "FTR_CAMERA")) tempHTML += "<td><a href=\"" + toDayOpenFuncStr + "\">" + getTranslation("DAN") + "</a> | <a href=\"" + toWeekOpenFuncStr + "\">" + getTranslation("TEDEN") + "</a> | <a href=\"" + toMonthOpenFuncStr + "\">" + getTranslation("MESEC") + "</a> | <a href=\"" + toYearOpenFuncStr + "\">" + getTranslation("LETO") + "</a></td>";
			tempHTML += "<td><a href=\"javascript:selectSensor(" + sensor.attr("id") + ")\">" + getTranslation("SELECT_SENSOR") + "</a></td>";
			if (keyname == "FTR_CAMERA") camera = 1;
			tempHTML += "</tr>";
			
			the_marker.set(getTranslation(keyname), 1);
		});
			
		tempHTML += "</table>";			
		// iwHTML += "<span class=\"span-time\"><b>" + getTranslation("LAST_MEASUREMENT") + ":</b> " + firstMeasurementTime + "<span>";			
		iwHTML += tempHTML;
		
		var iwContent;
		iwContent = "<div class='div-iw'>" + iwHTML + "</div>";
		var nodeid = node.attr("id");
		// if ((nodeid >= 4) && (nodeid <= 9) && (nodeid != 8)) iwContent = iwContent + "<div class='div-metadata-iw'>LED power consumption: 37,2 W<br>Dimmed LED power consumption (from 23:00 to 04:45): 31,8 W<br>Electricity saving: 1kWh/month [<a href='/sl/electricity-saving.html' target='_new'>more</a>]</div>";	
		// if (camera == 1) iwContent = iwContent + "<div class='div-picture-iw'><img src=\"/slike/camera.php?id=" + node.attr("id") + "\"></div>";
		
		var infowindow = new google.maps.InfoWindow({
      content: iwContent					
    });
		
		// filter out inactive nodes
		// if (firstMeasurementTime != "N/A") {				
      google.maps.event.addListener(the_marker, 'click', function() {
        infowindow.open(map, the_marker);
      });  
  		markers.push(the_marker);
		// } else {
		//  the_marker.visible = false;
		//	the_marker.setMap(null);			
		// }					
	});																				
			
	// markerCluster = new MarkerClusterer(map, markers, {maxZoom: 15});
} // loadedData  			

function loadedKeywords(data) {
  var HTML = "<font style=\"font-size: 13px\">" + getTranslation("SELECT_BY_FEATURE") + ":</font> ";
  $(data).find("keyword").each(function() {
	  var keyword = $(this);
	  var keywordName = keyword.attr("name");					
		var weight = keyword.attr("weight");
		size = Math.round(weight * 45) + 8;					
		HTML += "<font style=\"font-size: " + size + "px\"><a href=\"javascript:filterByKeyword('" + keywordName + "');\">" + keywordName + "</a></font> ";															
	});
	$("#div-keywords").html(HTML);
}

function loadedChart(myData) {
	// curtains down
	// $("#div-overlay").fadeIn();

	// make DOM
	xmlData = (new XMLSerializer()).serializeToString(myData);		  	
	// set position of the chart
	// var xPos = (getClientWidth() - $("#div-chart").width()) / 2;
	// var yPos = (getClientHeight() - $("#div-chart").height()) / 2;	
	
	// display layer					
	$("#div-chart").insertFusionCharts({
	  swfPath: "template/charts/",
		type: "MSLine2D",
		data: xmlData,
		dataFormat: "XMLData",
		width: "650",
		height: "400"
	});			
}

// INTERACTION FUNCTIONS - UI ------------------------------------------------


function startupUpdate() {    
}
	
function gotoCluster(clusterid) {
	var lat = clusters[clusterid][1];
	var lng = clusters[clusterid][2];
	var latlng = new google.maps.LatLng(lat, lng);
  map.panTo(latlng);
}
		
function filterByKeyword(keywordName) {
	markerCluster.clearMarkers();
  for (var i in markers) {			  				
	  if (markers[i].get(keywordName) != 1) {
		  markers[i].visible = false;
			markers[i].setMap(null);					
		} else {
		  markers[i].visible = true;
			markers[i].setMap(map);
			var newmarkers = [];
			newmarkers.push(markers[i]);
			markerCluster.addMarkers(newmarkers);					
		}
	}
	markerCluster.redraw();
}		

function showChart() {
  var sid = $("#ch-sensor option:selected").val();
	var sd = $("#ch-date").val();
	var type = $("#ch-type option:selected").val();
	var timespan = $("#ch-timespan option:selected").val();
	
	myUrl = "/xml/get-aggregates?p=" + sid + ":" + sd + ":" + type + ":" + timespan;
	//alert(url),
		
	$.ajax({
	  url: myUrl,
		success: loadedChart,
		dataType: "xml",
	});
}

function getRule() {
  if ($("#div-conditions").text() != "") alert("Rule saved to server!");
	else alert("No event defined!");
}

function getData() {
  if ($("#div-conditions").text() != "") alert("Rule saved to server!");
	else alert("No event defined!");
}


// read input parameters
var debug = gup('debug');

// main function				  		
$(function() {
  nowDate = new Date();
  eventTime = mysqlDateStr(nowDate) + " 00:00:00";
  $("#ch-date").val(mysqlDateStr(nowDate));
	
	// TODO: automatically set center of the map and zoom according to the clusters	
	// initialize Google maps
	var latlng = new google.maps.LatLng(20, 0);
	var myOptions = {
	  zoom: 2,
		center: latlng,
	  mapTypeControlOptions: {style: google.maps.MapTypeControlStyle.DROPDOWN_MENU, position: google.maps.ControlPosition.RIGHT_BOTTOM},
		mapTypeId: google.maps.MapTypeId.HYBRID
  };	
  map = new google.maps.Map(document.getElementById("map_canvas"), myOptions);  	
	
	google.maps.event.addListener(map, 'dragend', function() {
    // updateCurrentLocation(map.getCenter());
  	// updatePanoramio();
 	});

	
	// initialize nodes
	
	$.ajax({
	  url: '/xml/current-state',
		success: loadedData
	});

	// initialization of widgets with AJAX
	// news	
	/*
	$.ajax({			
	  url: '/index.php?id=5',
		success: loadedNews
	});
	*/
	
	// keyword cloud
	/*
	$.ajax({
	  url: '/xml/get-keywords',
		success: loadedKeywords
	});
	*/
	
	// cluster select box
	/*
	$.ajax({
	  url: '/xml/get-clusters',
		success: loadedClusters
	});
	*/
	
	$("#div-overlay").click(function() {
	  $("#div-chart").css("display", "none");
		$(this).fadeOut();		
	});
	
		
	// delayed update of some widgets, depending on google maps initialization
	// startupInterval = window.setInterval(startupUpdate, 2000);	
});
