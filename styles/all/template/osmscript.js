var click, map, layer_mapnik,  layer_tah,  layer_markers;
var fromProjection = new OpenLayers.Projection("EPSG:4326");   // Transform from WGS 1984
var toProjection   = new OpenLayers.Projection("EPSG:900913"); // to Spherical Mercator Projection

function drawmap(lon, lat, zoom, controls) {
	var cntrposition = new OpenLayers.LonLat(lon, lat).transform( fromProjection, toProjection);
	click = new OpenLayers.Control.Click();

	if(controls === true) {
		map = new OpenLayers.Map('map', {
			controls: [
				new OpenLayers.Control.Navigation(),
				new OpenLayers.Control.PanZoomBar()],
			numZoomLevels: 18,
			maxResolution: 156543,
			units: 'meters'
		});
	} else {
		map = new OpenLayers.Map('map', {
			numZoomLevels: 18,
			maxResolution: 156543,
			units: 'meters'
		});
	}


	layer_mapnik = new OpenLayers.Layer.OSM.Mapnik("Mapnik");
	layer_markers = new OpenLayers.Layer.Markers("Address", { projection: new OpenLayers.Projection("EPSG:4326"), visibility: true});
	map.addLayers([layer_mapnik, layer_markers]);
	map.addControl(click);

	jumpTo(lon, lat, zoom);
}

OpenLayers.Control.Click = OpenLayers.Class(OpenLayers.Control, {
	defaultHandlerOptions: {
		'single': true,
		'double': false,
		'pixelTolerance': 0,
		'stopSingle': false,
		'stopDouble': false
	},

	initialize: function(options) {
		this.handlerOptions = OpenLayers.Util.extend(
			{}, this.defaultHandlerOptions
		);
		OpenLayers.Control.prototype.initialize.apply(
			this, arguments
		);
		this.handler = new OpenLayers.Handler.Click(
			this, {
				'click': this.trigger
			}, this.handlerOptions
		);
	},

	trigger: function(e) {
		var lonlat = map.getLonLatFromPixel(e.xy);
		pos= new OpenLayers.LonLat(lonlat.lon,lonlat.lat).transform(toProjection,fromProjection);



	$('#map').css('cursor', 'crosshair');
	click.activate();
		set_postion(pos.lon, pos.lat);
	}
});

function set_position()
{
	alert_set_position();
	$('#map').css('cursor', 'crosshair');
	click.activate();
	return false;
}

function jumpTo(lon, lat, zoom) {
	var x = Lon2Merc(lon);
	var y = Lat2Merc(lat);
	map.setCenter(new OpenLayers.LonLat(x, y), zoom);
	return false;
}

function Lon2Merc(lon) {
	return 20037508.34 * lon / 180;
}

function Lat2Merc(lat) {
	var PI = 3.14159265358979323846;
	lat = Math.log(Math.tan( (90 + lat) * PI / 360)) / (PI / 180);
	return 20037508.34 * lat / 180;
}

function generateMarker(image){
	var size = new OpenLayers.Size(26,26);
	var offset = new OpenLayers.Pixel(-(size.w/2), -size.h);
	var i = new OpenLayers.Icon(image, size, offset);
	return i;
}

function addMarker(layer, lon, lat, popupContentHTML, marker) {

	var ll = new OpenLayers.LonLat(Lon2Merc(lon), Lat2Merc(lat));
	var feature = new OpenLayers.Feature(layer, ll);
	feature.closeBox = true;
	feature.popupClass = OpenLayers.Class(OpenLayers.Popup.FramedCloud, {minSize: new OpenLayers.Size(100, 10) } );
	feature.data.popupContentHTML = popupContentHTML;
	feature.data.overflow = "hidden";

	var marker = new OpenLayers.Marker(ll, marker);
	marker.feature = feature;
	var markerClick = function(evt) {
		if (this.popup == null) {
			this.popup = this.createPopup(this.closeBox);
			map.addPopup(this.popup);
			this.popup.show();
		} else {
			this.popup.toggle();
		}
		OpenLayers.Event.stop(evt);
	};
	marker.events.register("mousedown", feature, markerClick);

	layer.addMarker(marker);
}

$('#map').oncontextmenu = function noContextMenu(e) {
    if(!e){ //dear IE...
		var e = window.event;
		e.returnValue = false;
    }

    var f = layer_mapnik.getFeatureFromEvent(e);
    alert(f);
    //f is the pointed vector.feature :)

    return false; //Prevent display of browser context menu
}