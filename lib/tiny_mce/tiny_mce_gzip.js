var tinyMCE_GZ = {
	settings : {
		themes : '',
		plugins : '',
		languages : '',
		disk_cache : true,
		page_name : 'tiny_mce_gzip.php',
		debug : false,
		suffix : ''
	},

	init : function(s, cb, sc) {
		var t = this, n, i, nl = document.getElementsByTagName('script');

		for (n in s)
			t.settings[n] = s[n];

		s = t.settings;

		for (i=0; i<nl.length; i++) {
			n = nl[i];

			if (n.src && n.src.indexOf('tiny_mce') != -1)
				t.baseURL = n.src.substring(0, n.src.lastIndexOf('/'));
		}

		if (!t.coreLoaded)
			t.loadScripts(1, s.themes, s.plugins, s.languages, cb, sc);
	},

	loadScripts : function(co, th, pl, la, cb, sc) {
		var t = this, x, w = window, q, c = 0, ti, s = t.settings;

		function get(s) {
			x = 0;

			try {
				x = new ActiveXObject(s);
			} catch (s) {
			}

			return x;
		};

		// Build query string
		q = 'js=true&diskcache=' + (s.disk_cache ? 'true' : 'false') + '&core=' + (co ? 'true' : 'false') + '&suffix=' + escape(s.suffix) + '&themes=' + escape(th) + '&plugins=' + escape(pl) + '&languages=' + escape(la);

		if (co)
			t.coreLoaded = 1;

		// Send request
		x = w.XMLHttpRequest ? new XMLHttpRequest() : get('Msxml2.XMLHTTP') || get('Microsoft.XMLHTTP');
		x.overrideMimeType && x.overrideMimeType('text/javascript');
		x.open('GET', t.baseURL + '/' + s.page_name + '?' + q, !!cb);
//		x.setRequestHeader('Content-Type', 'text/javascript');
		x.send('');

		// Handle asyncronous loading
		if (cb) {
			// Wait for response
			ti = w.setInterval(function() {
				if (x.readyState == 4 || c++ > 10000) {
					w.clearInterval(ti);

					if (c < 10000 && x.status == 200) {
						t.loaded = 1;
						t.eval(x.responseText);
						tinymce.dom.Event.domLoaded = true;
						cb.call(sc || t, x);
					}

					ti = x = null;
				}
			}, 10);
		} else
			t.eval(x.responseText);
	},

	start : function() {
		var t = this, each = tinymce.each, s = t.settings, sl, ln = s.languages.split(',');

		tinymce.suffix = s.suffix;

		// Extend script loader
		tinymce.create('tinymce.compressor.ScriptLoader:tinymce.dom.ScriptLoader', {
			loadScripts : function(sc, cb, s) {
				var ti = this, th = [], pl = [], la = [];

				each(sc, function(o) {
					var u = o.url;

					if ((!ti.lookup[u] || ti.lookup[u].state != 2) && u.indexOf(t.baseURL) === 0) {
						// Collect theme
						if (u.indexOf('editor_template') != -1) {
							th.push(/\/themes\/([^\/]+)/.exec(u)[1]);
							load(u, 1);
						}

						// Collect plugin
						if (u.indexOf('editor_plugin') != -1) {
							pl.push(/\/plugins\/([^\/]+)/.exec(u)[1]);
							load(u, 1);
						}

						// Collect language
						if (u.indexOf('/langs/') != -1) {
							la.push(/\/langs\/([^.]+)/.exec(u)[1]);
							load(u, 1);
						}
					}
				});

				if (th.length + pl.length + la.length > 0) {
					if (sl.settings.strict_mode) {
						// Async
						t.loadScripts(0, th.join(','), pl.join(','), la.join(','), cb, s);
						return;
					} else
						t.loadScripts(0, th.join(','), pl.join(','), la.join(','), cb, s);
				}

				return ti.parent(sc, cb, s);
			}
		});

		sl = tinymce.ScriptLoader = new tinymce.compressor.ScriptLoader();

		function load(u, sp) {
			var o;

			if (!sp)
				u = t.baseURL + u;

			o = {url : u, state : 2};
			sl.queue.push(o);
			sl.lookup[o.url] = o;
		};

		// Add core languages
		each (ln, function(c) {
			if (c)
				load('/langs/' + c + '.js');
		});

		// Add themes with languages
		each(s.themes.split(','), function(n) {
			if (n) {
				load('/themes/' + n + '/editor_template' + s.suffix + '.js');

				each (ln, function(c) {
					if (c)
						load('/themes/' + n + '/langs/' + c + '.js');
				});
			}
		});

		// Add plugins with languages
		each(s.plugins.split(','), function(n) {
			if (n) {
				load('/plugins/' + n + '/editor_plugin' + s.suffix + '.js');

				each (ln, function(c) {
					if (c)
						load('/plugins/' + n + '/langs/' + c + '.js');
				});
			}
		});
	},

	end : function() {
	},

	eval : function(co) {
		var w = window;

		// Evaluate script
		if (!w.execScript) {
			if (/Gecko/.test(navigator.userAgent))
				eval(co, w); // Firefox 3.0
			else
				eval.call(w, co);
		} else
			w.execScript(co); // IE
	}
};
