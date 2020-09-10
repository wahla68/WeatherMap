<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Weather layer</title>
  <style>
  .container {
			height: 450px;
		}
    html, body{
      width: 100%;
      height: 100%;
      margin: 0;
      padding: 0;
    }
    #map-canvas {
      width: 100%;
      height:  50%;
    }
	#right-panel {
	  margin: 20px;
	  border-width: 2px;
	  width: 20%;
	  height: 400px;
	  float: left;
	  text-align: left;
	  padding-top: 0;
	}
	#directions-panel {
	  margin-top: 10px;
	  background-color: #FFEE77;
	  padding: 10px;
	  overflow: scroll;
	  height: 174px;
	}
	#right-panel {
	  font-family: 'Roboto','sans-serif';
	  line-height: 30px;
	  padding-left: 10px;
	}

	#right-panel select, #right-panel input {
	  font-size: 15px;
	}

	#right-panel select {
	  width: 100%;
	}

	#right-panel i {
	  font-size: 12px;
	}
    .gm-style-iw {
      text-align: center;
    }
	
  </style>
<script  src="https://maps.googleapis.com/maps/api/js?key=APIKEY"></script> 
</script>
<script>
  var map;
  var geoJSON;
  var request;
  var gettingData = false;
  var openWeatherMapKey = "f1717a935c6b54f78d4e0bddd579db95"
  function initialize() {
	var directionsService = new google.maps.DirectionsService;
	  var directionsDisplay = new google.maps.DirectionsRenderer;  
    var mapOptions = {
      zoom: 4,
      center: new google.maps.LatLng(50,-50)
    };
    map = new google.maps.Map(document.getElementById('map-canvas'),
        mapOptions);
	directionsDisplay.setMap(map);

  document.getElementById('submit').addEventListener('click', function() {
    calculateAndDisplayRoute(directionsService, directionsDisplay);
  });	
    // Add interaction listeners to make weather requests
    google.maps.event.addListener(map, 'idle', checkIfDataRequested);
    // Sets up and populates the info window with details
    map.data.addListener('click', function(event) {
      infowindow.setContent(
       "<img src=" + event.feature.getProperty("icon") + ">"
       + "<br /><strong>" + event.feature.getProperty("city") + "</strong>"
       + "<br />" + event.feature.getProperty("temperature") + "&deg;C"
       + "<br />" + event.feature.getProperty("weather")
       );
      infowindow.setOptions({
          position:{
            lat: event.latLng.lat(),
            lng: event.latLng.lng()
          },
          pixelOffset: {
            width: 0,
            height: -15
          }
        });
      infowindow.open(map);
    });
  }
  var checkIfDataRequested = function() {
    // Stop extra requests being sent
    while (gettingData === true) {
      request.abort();
      gettingData = false;
    }
    getCoords();
  };
  // Get the coordinates from the Map bounds
  var getCoords = function() {
    var bounds = map.getBounds();
    var NE = bounds.getNorthEast();
    var SW = bounds.getSouthWest();
    getWeather(NE.lat(), NE.lng(), SW.lat(), SW.lng());
  };
  // Make the weather request
  var getWeather = function(northLat, eastLng, southLat, westLng) {
    gettingData = true;
    var requestString = "http://api.openweathermap.org/data/2.5/box/city?bbox="
                        + westLng + "," + northLat + "," //left top
                        + eastLng + "," + southLat + "," //right bottom
                        + map.getZoom()
                        + "&cluster=yes&format=json"
                        + "&APPID=" + openWeatherMapKey;
    request = new XMLHttpRequest();
    request.onload = proccessResults;
    request.open("get", requestString, true);
    request.send();
  };
  // Take the JSON results and proccess them
  var proccessResults = function() {
    console.log(this);
    var results = JSON.parse(this.responseText);
    if (results.list.length > 0) {
        resetData();
        for (var i = 0; i < results.list.length; i++) {
          geoJSON.features.push(jsonToGeoJson(results.list[i]));
        }
        drawIcons(geoJSON);
    }
  };
  var infowindow = new google.maps.InfoWindow();
  // For each result that comes back, convert the data to geoJSON
  var jsonToGeoJson = function (weatherItem) {
    var feature = {
      type: "Feature",
      properties: {
        city: weatherItem.name,
        weather: weatherItem.weather[0].main,
        temperature: weatherItem.main.temp,
        min: weatherItem.main.temp_min,
        max: weatherItem.main.temp_max,
        humidity: weatherItem.main.humidity,
        pressure: weatherItem.main.pressure,
        windSpeed: weatherItem.wind.speed,
        windDegrees: weatherItem.wind.deg,
        windGust: weatherItem.wind.gust,
        icon: "http://openweathermap.org/img/w/"
              + weatherItem.weather[0].icon  + ".png",
        coordinates: [weatherItem.coord.Lon, weatherItem.coord.Lat]
      },
      geometry: {
        type: "Point",
        coordinates: [weatherItem.coord.Lon, weatherItem.coord.Lat]
      }
    };
    // Set the custom marker icon
    map.data.setStyle(function(feature) {
      return {
        icon: {
          url: feature.getProperty('icon'),
          anchor: new google.maps.Point(25, 25)
        }
      };
    });
    // returns object
    return feature;
  };
  // Add the markers to the map
  var drawIcons = function (weather) {
     map.data.addGeoJson(geoJSON);
     // Set the flag to finished
     gettingData = false;
  };
  // Clear data layer and geoJSON
  var resetData = function () {
    geoJSON = {
      type: "FeatureCollection",
      features: []
    };
    map.data.forEach(function(feature) {
      map.data.remove(feature);
    });
  };
  google.maps.event.addDomListener(window, 'load', initialize);
 

  
  
  


function calculateAndDisplayRoute(directionsService, directionsDisplay) {
  var waypts = [];
  var checkboxArray = document.getElementById('waypoints');
  for (var i = 0; i < checkboxArray.length; i++) {
    if (checkboxArray.options[i].selected) {
      waypts.push({
        location: checkboxArray[i].value,
        stopover: true
      });
    }
  }

  directionsService.route({
    origin: document.getElementById('start').value,
    destination: document.getElementById('end').value,
    waypoints: waypts,
    optimizeWaypoints: true,
    travelMode: 'DRIVING'
  }, function(response, status) {
    if (status === 'OK') {
      directionsDisplay.setDirections(response);
      var route = response.routes[0];
      var summaryPanel = document.getElementById('directions-panel');
      summaryPanel.innerHTML = '';
      // For each route, display summary information.
      for (var i = 0; i < route.legs.length; i++) {
        var routeSegment = i + 1;
        summaryPanel.innerHTML += '<b>Route Segment: ' + routeSegment +
            '</b><br>';
        summaryPanel.innerHTML += route.legs[i].start_address + ' to ';
        summaryPanel.innerHTML += route.legs[i].end_address + '<br>';
        summaryPanel.innerHTML += route.legs[i].distance.text + '<br><br>';
      }
    } else {
      window.alert('Directions request failed due to ' + status);
    }
  });
}
</script>
</head>
<body>
<div class="container">
		<center><h1>Weather Map</h1></center>
		<div id="map-canvas"></div>
		<div id="right-panel"></div>
	<b>Start:</b>
<select id="start">
  <option value="Halifax, NS">Halifax, NS</option>
  <option value="Boston, MA">Boston, MA</option>
  <option value="New York, NY">New York, NY</option>
  <option value="Miami, FL">Miami, FL</option>
</select>
<br>
<b>Waypoints:</b> <br>
<i>(Ctrl+Click or Cmd+Click for multiple selection)</i> <br>
<select multiple id="waypoints">
  <option value="montreal, quebec">Montreal, QBC</option>
  <option value="toronto, ont">Toronto, ONT</option>
  <option value="chicago, il">Chicago</option>
  <option value="winnipeg, mb">Winnipeg</option>
  <option value="fargo, nd">Fargo</option>
  <option value="calgary, ab">Calgary</option>
  <option value="spokane, wa">Spokane</option>
</select>
<br>
<b>End:</b>
<select id="end">
  <option value="Vancouver, BC">Vancouver, BC</option>
  <option value="Seattle, WA">Seattle, WA</option>
  <option value="San Francisco, CA">San Francisco, CA</option>
  <option value="Los Angeles, CA">Los Angeles, CA</option>
</select>
<br>
  <input type="submit" id="submit">
</div>
<div id="directions-panel"></div>	


<div id="map-canvas"></div>

</body>
</html>