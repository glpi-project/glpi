/**
 * @author Ryan Johnson <ryan@livepipe.net>
 * @copyright 2007 LivePipe LLC
 * @package Control.Modal
 * @license MIT
 * @url http://livepipe.net/projects/control_modal/
 * @version 2.2.3
 */

if(typeof(Control) == "undefined")
	Control = {};
Control.Modal = Class.create();
Object.extend(Control.Modal,{
	loaded: false,
	loading: false,
	loadingTimeout: false,
	overlay: false,
	container: false,
	current: false,
	ie: false,
	effects: {
		containerFade: false,
		containerAppear: false,
		overlayFade: false,
		overlayAppear: false
	},
	targetRegexp: /#(.+)$/,
	imgRegexp: /\.(jpe?g|gif|png|tiff?)$/i,
	overlayStyles: {
		position: 'fixed',
		top: 0,
		left: 0,
		width: '100%',
		height: '100%',
		zIndex: 9998
	},
	overlayIEStyles: {
		position: 'absolute',
		top: 0,
		left: 0,
		zIndex: 9998
	},
	disableHoverClose: false,
	load: function(){
		if(!Control.Modal.loaded){
			Control.Modal.loaded = true;
			Control.Modal.ie = !(typeof document.body.style.maxHeight != 'undefined');
			Control.Modal.overlay = $(document.createElement('div'));
			Control.Modal.overlay.id = 'modal_overlay';
			Object.extend(Control.Modal.overlay.style,Control.Modal['overlay' + (Control.Modal.ie ? 'IE' : '') + 'Styles']);
			Control.Modal.overlay.hide();
			Control.Modal.container = $(document.createElement('div'));
			Control.Modal.container.id = 'modal_container';
			Control.Modal.container.hide();
			Control.Modal.loading = $(document.createElement('div'));
			Control.Modal.loading.id = 'modal_loading';
			Control.Modal.loading.hide();
			var body_tag = document.getElementsByTagName('body')[0];
			body_tag.appendChild(Control.Modal.overlay);
			body_tag.appendChild(Control.Modal.container);
			body_tag.appendChild(Control.Modal.loading);
			Control.Modal.container.observe('mouseout',function(event){
				if(!Control.Modal.disableHoverClose && Control.Modal.current && Control.Modal.current.options.hover && !Position.within(Control.Modal.container,Event.pointerX(event),Event.pointerY(event)))
					Control.Modal.close();
			});
		}
	},
	open: function(contents,options){
		options = options || {};
		if(!options.contents)
			options.contents = contents;
		var modal_instance = new Control.Modal(false,options);
		modal_instance.open();
		return modal_instance;
	},
	close: function(force){
		if(typeof(force) != 'boolean')
			force = false;
		if(Control.Modal.current)
			Control.Modal.current.close(force);
	},
	attachEvents: function(){
		Event.observe(window,'load',Control.Modal.load);
		Event.observe(window,'unload',Event.unloadCache,false);
	},
	center: function(element){
		if(!element._absolutized){
			element.setStyle({
				position: 'absolute'
			}); 
			element._absolutized = true;
		}
		var dimensions = element.getDimensions();
		Position.prepare();
		var offset_left = (Position.deltaX + Math.floor((Control.Modal.getWindowWidth() - dimensions.width) / 2));
		var offset_top = (Position.deltaY + ((Control.Modal.getWindowHeight() > dimensions.height) ? Math.floor((Control.Modal.getWindowHeight() - dimensions.height) / 2) : 0));
		element.setStyle({
			top: ((dimensions.height <= Control.Modal.getDocumentHeight()) ? ((offset_top != null && offset_top > 0) ? offset_top : '0') + 'px' : 0),
			left: ((dimensions.width <= Control.Modal.getDocumentWidth()) ? ((offset_left != null && offset_left > 0) ? offset_left : '0') + 'px' : 0)
		});
	},
	getWindowWidth: function(){
		return (self.innerWidth || document.documentElement.clientWidth || document.body.clientWidth || 0);
	},
	getWindowHeight: function(){
		return (self.innerHeight ||  document.documentElement.clientHeight || document.body.clientHeight || 0);
	},
	getDocumentWidth: function(){
		return Math.min(document.body.scrollWidth,Control.Modal.getWindowWidth());
	},
	getDocumentHeight: function(){
		return Math.max(document.body.scrollHeight,Control.Modal.getWindowHeight());
	},
	onKeyDown: function(event){
		if(event.keyCode == Event.KEY_ESC)
			Control.Modal.close();
	}
});
Object.extend(Control.Modal.prototype,{
	mode: '',
	html: false,
	href: '',
	element: false,
	src: false,
	imageLoaded: false,
	ajaxRequest: false,
	initialize: function(element,options){
		this.element = $(element);
		this.options = {
			beforeOpen: Prototype.emptyFunction,
			afterOpen: Prototype.emptyFunction,
			beforeClose: Prototype.emptyFunction,
			afterClose: Prototype.emptyFunction,
			onSuccess: Prototype.emptyFunction,
			onFailure: Prototype.emptyFunction,
			onException: Prototype.emptyFunction,
			beforeImageLoad: Prototype.emptyFunction,
			afterImageLoad: Prototype.emptyFunction,
			autoOpenIfLinked: true,
			contents: false,
			loading: false, //display loading indicator
			fade: false,
			fadeDuration: 0.75,
			image: false,
			imageCloseOnClick: true,
			hover: false,
			iframe: false,
			iframeTemplate: new Template('<iframe src="#{href}" width="100%" height="100%" frameborder="0" id="#{id}"></iframe>'),
			evalScripts: true, //for Ajax, define here instead of in requestOptions
			requestOptions: {}, //for Ajax.Request
			overlayDisplay: true,
			overlayClassName: '',
			overlayCloseOnClick: true,
			containerClassName: '',
			opacity: 0.3,
			zIndex: 9998,
			width: null,
			height: null,
			offsetLeft: 0, //for use with 'relative'
			offsetTop: 0, //for use with 'relative'
			position: 'absolute' //'absolute' or 'relative'
		};
		Object.extend(this.options,options || {});
		var target_match = false;
		var image_match = false;
		if(this.element){
			target_match = Control.Modal.targetRegexp.exec(this.element.href);
			image_match = Control.Modal.imgRegexp.exec(this.element.href);
		}
		if(this.options.position == 'mouse')
			this.options.hover = true;
		if(this.options.contents){
			this.mode = 'contents';
		}else if(this.options.image || image_match){
			this.mode = 'image';
			this.src = this.element.href;
		}else if(target_match){
			this.mode = 'named';
			var x = $(target_match[1]);
			this.html = x.innerHTML;
			x.remove();
			this.href = target_match[1];
		}else{
			this.mode = (this.options.iframe) ? 'iframe' : 'ajax';
			this.href = this.element.href;
		}
		if(this.element){
			if(this.options.hover){
				this.element.observe('mouseover',this.open.bind(this));
				this.element.observe('mouseout',function(event){
					if(!Position.within(Control.Modal.container,Event.pointerX(event),Event.pointerY(event)))
						this.close();
				}.bindAsEventListener(this));
			}else{
				this.element.onclick = function(event){
					this.open();
					Event.stop(event);
					return false;
				}.bindAsEventListener(this);
			}
		}
		var targets = Control.Modal.targetRegexp.exec(window.location);
		this.position = function(event){
			if(this.options.position == 'absolute')
				Control.Modal.center(Control.Modal.container);
			else{
				var xy = (event && this.options.position == 'mouse' ? [Event.pointerX(event),Event.pointerY(event)] : Position.cumulativeOffset(this.element));
				Control.Modal.container.setStyle({
					position: 'absolute',
					top: xy[1] + (typeof(this.options.offsetTop) == 'function' ? this.options.offsetTop() : this.options.offsetTop) + 'px',
					left: xy[0] + (typeof(this.options.offsetLeft) == 'function' ? this.options.offsetLeft() : this.options.offsetLeft) + 'px'
				});
			}
			if(Control.Modal.ie){
				Control.Modal.overlay.setStyle({
					height: Control.Modal.getDocumentHeight() + 'px',
					width: Control.Modal.getDocumentWidth() + 'px'
				});
			}
		}.bind(this);
		if(this.mode == 'named' && this.options.autoOpenIfLinked && targets && targets[1] && targets[1] == this.href)
			this.open();
	},
	showLoadingIndicator: function(){
		if(this.options.loading){
			Control.Modal.loadingTimeout = window.setTimeout(function(){
				var modal_image = $('modal_image');
				if(modal_image)
					modal_image.hide();
				Control.Modal.loading.style.zIndex = this.options.zIndex + 1;
				Control.Modal.loading.update('<img id="modal_loading" src="' + this.options.loading + '"/>');
				Control.Modal.loading.show();
				Control.Modal.center(Control.Modal.loading);
			}.bind(this),250);
		}
	},
	hideLoadingIndicator: function(){
		if(this.options.loading){
			if(Control.Modal.loadingTimeout)
				window.clearTimeout(Control.Modal.loadingTimeout);
			var modal_image = $('modal_image');
			if(modal_image)
				modal_image.show();
			Control.Modal.loading.hide();
		}
	},
	open: function(force){
		if(!force && this.notify('beforeOpen') === false)
			return;
		if(!Control.Modal.loaded)
			Control.Modal.load();
		Control.Modal.close();
		if(!this.options.hover)
			Event.observe($(document.getElementsByTagName('body')[0]),'keydown',Control.Modal.onKeyDown);
		Control.Modal.current = this;
		if(!this.options.hover)
			Control.Modal.overlay.setStyle({
				zIndex: this.options.zIndex,
				opacity: this.options.opacity
			});
		Control.Modal.container.setStyle({
			zIndex: this.options.zIndex + 1,
			width: (this.options.width ? (typeof(this.options.width) == 'function' ? this.options.width() : this.options.width) + 'px' : null),
			height: (this.options.height ? (typeof(this.options.height) == 'function' ? this.options.height() : this.options.height) + 'px' : null)
		});
		if(Control.Modal.ie && !this.options.hover){
			$A(document.getElementsByTagName('select')).each(function(select){
				select.style.visibility = 'hidden';
			});
		}
		Control.Modal.overlay.addClassName(this.options.overlayClassName);
		Control.Modal.container.addClassName(this.options.containerClassName);
		switch(this.mode){
			case 'image':
				this.imageLoaded = false;
				this.notify('beforeImageLoad');
				this.showLoadingIndicator();
				var img = document.createElement('img');
				img.onload = function(img){
					this.hideLoadingIndicator();
					this.update([img]);
					if(this.options.imageCloseOnClick)
						$(img).observe('click',Control.Modal.close);
					this.position();
					this.notify('afterImageLoad');
					img.onload = null;
				}.bind(this,img);
				img.src = this.src;
				img.id = 'modal_image';
				break;
			case 'ajax':
				this.notify('beforeLoad');
				var options = {
					method: 'post',
					onSuccess: function(request){
						this.hideLoadingIndicator();
						this.update(request.responseText);
						this.notify('onSuccess',request);
						this.ajaxRequest = false;
					}.bind(this),
					onFailure: function(){
						this.notify('onFailure');
					}.bind(this),
					onException: function(){
						this.notify('onException');
					}.bind(this)
				};
				Object.extend(options,this.options.requestOptions);
				this.showLoadingIndicator();
				this.ajaxRequest = new Ajax.Request(this.href,options);
				break;
			case 'iframe':
				this.update(this.options.iframeTemplate.evaluate({href: this.href, id: 'modal_iframe'}));
				break;
			case 'contents':
				this.update((typeof(this.options.contents) == 'function' ? this.options.contents() : this.options.contents));
				break;
			case 'named':
				this.update(this.html);
				break;
		}
		if(!this.options.hover){
			if(this.options.overlayCloseOnClick && this.options.overlayDisplay)
				Control.Modal.overlay.observe('click',Control.Modal.close);
			if(this.options.overlayDisplay){
				if(this.options.fade){
					if(Control.Modal.effects.overlayFade)
						Control.Modal.effects.overlayFade.cancel();
					Control.Modal.effects.overlayAppear = new Effect.Appear(Control.Modal.overlay,{
						queue: {
							position: 'front',
							scope: 'Control.Modal'
						},
						to: this.options.opacity,
						duration: this.options.fadeDuration / 2
					});
				}else
					Control.Modal.overlay.show();
			}
		}
		if(this.options.position == 'mouse'){
			this.mouseHoverListener = this.position.bindAsEventListener(this);
			this.element.observe('mousemove',this.mouseHoverListener);
		}
		this.notify('afterOpen');
	},
	update: function(html){
		if(typeof(html) == 'string')
			Control.Modal.container.update(html);
		else{
			Control.Modal.container.update('');
			(html.each) ? html.each(function(node){
				Control.Modal.container.appendChild(node);
			}) : Control.Modal.container.appendChild(node);
		}
		if(this.options.fade){
			if(Control.Modal.effects.containerFade)
				Control.Modal.effects.containerFade.cancel();
			Control.Modal.effects.containerAppear = new Effect.Appear(Control.Modal.container,{
				queue: {
					position: 'end',
					scope: 'Control.Modal'
				},
				to: 1,
				duration: this.options.fadeDuration / 2
			});
		}else
			Control.Modal.container.show();
		this.position();
		Event.observe(window,'resize',this.position,false);
		Event.observe(window,'scroll',this.position,false);
	},
	close: function(force){
		if(!force && this.notify('beforeClose') === false)
			return;
		if(this.ajaxRequest)
			this.ajaxRequest.transport.abort();
		this.hideLoadingIndicator();	
		if(this.mode == 'image'){
			var modal_image = $('modal_image');
			if(this.options.imageCloseOnClick && modal_image)
				modal_image.stopObserving('click',Control.Modal.close);
		}
		if(Control.Modal.ie && !this.options.hover){
			$A(document.getElementsByTagName('select')).each(function(select){
				select.style.visibility = 'visible';
			});			
		}
		if(!this.options.hover)
			Event.stopObserving(window,'keyup',Control.Modal.onKeyDown);
		Control.Modal.current = false;
		Event.stopObserving(window,'resize',this.position,false);
		Event.stopObserving(window,'scroll',this.position,false);
		if(!this.options.hover){
			if(this.options.overlayCloseOnClick && this.options.overlayDisplay)
				Control.Modal.overlay.stopObserving('click',Control.Modal.close);
			if(this.options.overlayDisplay){
				if(this.options.fade){
					if(Control.Modal.effects.overlayAppear)
						Control.Modal.effects.overlayAppear.cancel();
					Control.Modal.effects.overlayFade = new Effect.Fade(Control.Modal.overlay,{
						queue: {
							position: 'end',
							scope: 'Control.Modal'
						},
						from: this.options.opacity,
						to: 0,
						duration: this.options.fadeDuration / 2
					});
				}else
					Control.Modal.overlay.hide();
			}
		}
		if(this.options.fade){
			if(Control.Modal.effects.containerAppear)
				Control.Modal.effects.containerAppear.cancel();
			Control.Modal.effects.containerFade = new Effect.Fade(Control.Modal.container,{
				queue: {
					position: 'front',
					scope: 'Control.Modal'
				},
				from: 1,
				to: 0,
				duration: this.options.fadeDuration / 2,
				afterFinish: function(){
					Control.Modal.container.update('');
					this.resetClassNameAndStyles();
				}.bind(this)
			});
		}else{
			Control.Modal.container.hide();
			Control.Modal.container.update('');
			this.resetClassNameAndStyles();
		}
		if(this.options.position == 'mouse')
			this.element.stopObserving('mousemove',this.mouseHoverListener);
		this.notify('afterClose');
	},
	resetClassNameAndStyles: function(){
		Control.Modal.overlay.removeClassName(this.options.overlayClassName);
		Control.Modal.container.removeClassName(this.options.containerClassName);
		Control.Modal.container.setStyle({
			height: null,
			width: null,
			top: null,
			left: null
		});
	},
	notify: function(event_name){
		try{
			if(this.options[event_name])
				return [this.options[event_name].apply(this.options[event_name],$A(arguments).slice(1))];
		}catch(e){
			if(e != $break)
				throw e;
			else
				return false;
		}
	}
});
if(typeof(Object.Event) != 'undefined')
	Object.Event.extend(Control.Modal);
Control.Modal.attachEvents();