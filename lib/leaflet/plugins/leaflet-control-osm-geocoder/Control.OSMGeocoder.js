if (typeof console == "undefined") {
	this.console = { log: function (msg) { /* do nothing since it would otherwise break IE */} };
}


L.Control.OSMGeocoder = L.Control.extend({
	options: {
		collapsed: true,
		position: 'topright',
		text: 'Locate',
		placeholder: '',
		bounds: null, // L.LatLngBounds
		email: null, // String
		callback: function (results) {
			if (results.length == 0) {
				console.log("ERROR: didn't find a result");
				return;
			}
			var bbox = results[0].boundingbox,
				first = new L.LatLng(bbox[0], bbox[2]),
				second = new L.LatLng(bbox[1], bbox[3]),
				bounds = new L.LatLngBounds([first, second]);
			this._map.fitBounds(bounds);
		}
	},

	_callbackId: 0,

	initialize: function (options) {
		L.Util.setOptions(this, options);
	},

	onAdd: function (map) {
		this._map = map;

		var className = 'leaflet-control-geocoder',
			container = this._container = L.DomUtil.create('div', className);

		L.DomEvent.disableClickPropagation(container);

		var form = this._form = L.DomUtil.create('form', className + '-form');

		var input = this._input = document.createElement('input');
		input.type = "text";
		input.placeholder = this.options.placeholder || '';

		var submit = document.createElement('input');
		submit.type = "submit";
		submit.value = this.options.text;

		form.appendChild(input);
		form.appendChild(submit);

		L.DomEvent.addListener(form, 'submit', this._geocode, this);

		if (this.options.collapsed) {
			L.DomEvent.addListener(container, 'mouseover', this._expand, this);
			L.DomEvent.addListener(container, 'mouseout', this._collapse, this);

			var link = this._layersLink = L.DomUtil.create('a', className + '-toggle', container);
			link.href = '#';
			link.title = 'Nominatim Geocoder';

			L.DomEvent.addListener(link, L.Browser.touch ? 'click' : 'focus', this._expand, this);

			this._map.on('movestart', this._collapse, this);
		} else {
			this._expand();
		}

		container.appendChild(form);

		return container;
	},

	/* helper functions for cordinate extraction */
	_createSearchResult : function(lat, lon) {
		//creates an position description similar to the result of a Nominatim search
		var diff = 0.005;
		var result = [];
		result[0] = {};
		result[0]["boundingbox"] = [parseFloat(lat)-diff,parseFloat(lat)+diff,parseFloat(lon)-diff,parseFloat(lon)+diff];
		result[0]["class"]="boundary";
		result[0]["display_name"]="Position: "+lat+" "+lon;
		result[0]["lat"] = lat;
		result[0]["lon"] = lon;
		return result;
	},
	_isLatLon : function (q) {
		//"lon lat" => xx.xxx x.xxxxx
		var re = /(-?\d+\.\d+)\s(-?\d+\.\d+)/;
		var m = re.exec(q);
		if (m != undefined) return m;

		//lat...xx.xxx...lon...x.xxxxx
		re = /lat\D*(-?\d+\.\d+)\D*lon\D*(-?\d+\.\d+)/;
		m = re.exec(q);
		//showRegExpResult(m);
		if (m != undefined) return m;
		else return null;
	},
	_isLatLon_decMin : function (q) {
		console.log("is LatLon?: "+q);
		//N 53° 13.785' E 010° 23.887'
		//re = /[NS]\s*(\d+)\D*(\d+\.\d+).?\s*[EW]\s*(\d+)\D*(\d+\.\d+)\D*/;
		re = /([ns])\s*(\d+)\D*(\d+\.\d+).?\s*([ew])\s*(\d+)\D*(\d+\.\d+)/i;
		m = re.exec(q.toLowerCase());
		//showRegExpResult(m);
		if ((m != undefined)) return m;
		else return null;
		// +- dec min +- dec min
	},

	_geocode : function (event) {
		L.DomEvent.preventDefault(event);
		var q = this._input.value;
		//try to find corrdinates
		if (this._isLatLon(q) != null)
		{
			var m = this._isLatLon(q);
			console.log("LatLon: "+m[1]+" "+m[2]);
			//m = {lon, lat}
			this.options.callback.call(this, this._createSearchResult(m[1],m[2]));
			return;
		}
		else if (this._isLatLon_decMin(q) != null)
		{
			var m = this._isLatLon_decMin(q);
			//m: [ns, lat dec, lat min, ew, lon dec, lon min]
			var temp  = new Array();
			temp['n'] = 1;
			temp['s'] = -1;
			temp['e'] = 1;
			temp['w'] = -1;
			this.options.callback.call(this,this._createSearchResult(
				temp[m[1]]*(Number(m[2]) + m[3]/60),
				temp[m[4]]*(Number(m[5]) + m[6]/60)
			));
			return;
		}

		//and now Nominatim
		//http://wiki.openstreetmap.org/wiki/Nominatim
		console.log(this._callbackId);
		window[("_l_osmgeocoder_"+this._callbackId)] = L.Util.bind(this.options.callback, this);


		/* Set up params to send to Nominatim */
		var params = {
			// Defaults
			q: this._input.value,
			json_callback : ("_l_osmgeocoder_"+this._callbackId++),
			format: 'json'
		};

		if (this.options.bounds && this.options.bounds != null) {
			if( this.options.bounds instanceof L.LatLngBounds ) {
				params.viewbox = this.options.bounds.toBBoxString();
				params.bounded = 1;
			}
			else {
				console.log('bounds must be of type L.LatLngBounds');
				return;
			}
		}

		if (this.options.email && this.options.email != null) {
			if (typeof this.options.email == 'string') {
				params.email = this.options.email;
			}
			else{
				console.log('email must be a string');
			}
		}

		var protocol = location.protocol;
		if (protocol == "file:") protocol = "https:";
		var url = protocol + "//nominatim.openstreetmap.org/search" + L.Util.getParamString(params),
			script = document.createElement("script");




		script.type = "text/javascript";
		script.src = url;
		script.id = this._callbackId;
		document.getElementsByTagName("head")[0].appendChild(script);
	},

	_expand: function () {
		L.DomUtil.addClass(this._container, 'leaflet-control-geocoder-expanded');
	},

	_collapse: function () {
		this._container.className = this._container.className.replace(' leaflet-control-geocoder-expanded', '');
	}
});
