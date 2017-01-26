/************************************************************************
*************************************************************************
@Name :       	BackToTop - jQuery Plugin
@Revison :    	1.0
@Date : 		12/2011
@Author:     	ALPIXEL - (www.myjqueryplugins.com - www.alpixel.fr)
@Support:    	FF, IE7, IE8, MAC Firefox, MAC Safari
@License :		Open Source - MIT License : http://www.opensource.org/licenses/mit-license.php
 
**************************************************************************
*************************************************************************/

/** BackToTop Plugin **/
(function($){

	$.BackToTop = {
		/**************/
		/** OPTIONS **/
		/**************/
		defaults: {
			/* vars */
			text : 'Back to top',
			autoShow : true,
			autoShowOffset : 0,
			timeEffect : 500,
			effectScroll : 'linear',
			appearMethod : 'slide',
         callback: undefined
		},
		
		/*****************/
		/** Init Method **/
		/*****************/
		init:function(options){
			/* vars **/
			opts = $.extend({}, $.BackToTop.defaults, options),
			/** Construct the link **/
			$.BackToTop._constructLink();
			
			
			/** Appear link when scrolling the window **/
			if(opts.autoShow)
			$(window).scroll(function(){
				if($(this).scrollTop() > opts.autoShowOffset) {
					switch (opts.appearMethod) {
						case 'fade' : divBack.fadeIn('fast'); break;
						case 'slide' : divBack.slideDown('fast'); break;
						default : divBack.show();	
					}
               if (typeof opts.callback != 'undefined') {
                  opts.callback('on');
               }
				}
				else {
					switch (opts.appearMethod) {
						case 'fade' : divBack.fadeOut('fast'); break;
						case 'slide' : divBack.slideUp('fast'); break;
						default : divBack.hide();	
					}
               if (typeof opts.callback != 'undefined') {
                  opts.callback('off');
               }
				}
			});
			
			/** ZScroll to top page on click **/
			$('#BackToTop').click(function(e) {
				e.preventDefault();
				$('body,html').animate({scrollTop:0},opts.timeEffect,opts.effectScroll);
			});
		},
		
		/** Create the link **/
		_constructLink:function() {
			divBack = $('<a />',{
				id : 'BackToTop',
				href : '#body',
				html : '<span>'+opts.text+'</span>'
			}).prependTo('body');
			if(!opts.autoShow) divBack.show();
		}
	
	};
	
	/** Init method **/
	BackToTop = function(options) {
		$.BackToTop.init(options);
	};
})(jQuery); 
