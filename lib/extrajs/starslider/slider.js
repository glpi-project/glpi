Ext.StarSlider = Ext.extend(Ext.BoxComponent, {	
    minValue: 0,
    maxValue: 100,
    keyIncrement: 1,
    increment: 1,
    clickRange: [5,15],
    clickToChange : true,
    animate: true,
    dragging: false,
    initComponent : function(){
        if(this.value === undefined){
            this.value = this.minValue;
        }
        Ext.StarSlider.superclass.initComponent.call(this);
        this.keyIncrement = Math.max(this.increment, this.keyIncrement); 
        this.addEvents(
            		
			'beforechange',
			'change',
			'changecomplete',
			'dragstart',
			'drag',
			'click',
			'dragend'
		);
    },

	
    onRender : function(){
        this.autoEl = {
            cls: 'x-starslider x-starslider-horz',
            cn:{cls:'x-starslider-end',cn:{cls:'x-starslider-inner',cn:[{cls:'x-starslider-thumb'},{tag:'a', cls:'x-starslider-focus', href:"#", tabIndex: '-1', hidefocus:'on'}]}}
        };
	this.width = (this.maxValue / this.increment)*16+8;
        Ext.StarSlider.superclass.onRender.apply(this, arguments);
        this.endEl = this.el.first();
        this.innerEl = this.endEl.first();
        this.thumb = this.innerEl.first();
        this.halfThumb = this.thumb.getWidth()/2;
        this.focusEl = this.thumb.next();
        this.initEvents();
        this.resizeThumb(this.translateValue(this.value), false);

    },

	
    initEvents : function(){
        this.thumb.addClassOnOver('x-starslider-thumb-over');
        this.mon(this.el, 'mousedown', this.onMouseDown, this);
        this.mon(this.el, 'keydown', this.onKeyDown, this);

        this.focusEl.swallowEvent("click", true);

        this.tracker = new Ext.dd.DragTracker({
            onBeforeStart: this.onBeforeDragStart.createDelegate(this),
            onStart: this.onDragStart.createDelegate(this),
            onDrag: this.onDrag.createDelegate(this),
            onEnd: this.onDragEnd.createDelegate(this),
            tolerance: 3,
            autoStart: 300
        });
        this.tracker.initEl(this.thumb);
        this.on('beforedestroy', this.tracker.destroy, this.tracker);
    },

	
    onMouseDown : function(e){
        if(this.disabled) {return;}

        var local = this.innerEl.translatePoints(e.getXY());
        this.onClickChange(local);
        this.focus();
    },

	
    onClickChange : function(local){
        if(local.top > this.clickRange[0] && local.top < this.clickRange[1]){
            this.setValue(Math.round(this.reverseValue(local.left)), undefined, true);
        }
    },
	
	
    onKeyDown : function(e){
        if(this.disabled){e.preventDefault();return;}
        var k = e.getKey();
        switch(k){
            case e.UP:
            case e.RIGHT:
                e.stopEvent();
                if(e.ctrlKey){
                    this.setValue(this.maxValue, undefined, true);
                }else{
                    this.setValue(this.value+this.keyIncrement, undefined, true);
                }
            break;
            case e.DOWN:
            case e.LEFT:
                e.stopEvent();
                if(e.ctrlKey){
                    this.setValue(this.minValue, undefined, true);
                }else{
                    this.setValue(this.value-this.keyIncrement, undefined, true);
                }
            break;
            default:
                e.preventDefault();
        }
    },
	
	
    doSnap : function(value){
        if(!this.increment || this.increment == 1 || !value) {
            return value;
        }
        var newValue = value, inc = this.increment;
        var m = value % inc;
        if(m > 0){
            if(m > (inc/2)){
                newValue = value + (inc-m);
            }else{
                newValue = value - m;
            }
        }
        return newValue.constrain(this.minValue,  this.maxValue);
    },
	
	
    afterRender : function(){
        Ext.StarSlider.superclass.afterRender.apply(this, arguments);
        if(this.value !== undefined){
            var v = this.normalizeValue(this.value);
            if(v !== this.value){
                delete this.value;
                this.setValue(v, false);
            } else {
                this.moveThumb(this.translateValue(v), false);
            }
        }
    },

	
    getRatio : function(){
        var w = this.innerEl.getWidth();
        var v = this.maxValue - this.minValue;
        return v == 0 ? w : (w/v);
    },

	
    normalizeValue : function(v){
       if(typeof v != 'number'){
            v = parseInt(v);
        }
        v = Math.round(v);
        v = this.doSnap(v);
        v = v.constrain(this.minValue, this.maxValue);
        return v;
    },

	
    setValue : function(v, animate, changeComplete){
        v = this.normalizeValue(v);
        if(v !== this.value && this.fireEvent('beforechange', this, v, this.value) !== false){
            this.value = v;
            this.resizeThumb(this.translateValue(v), animate !== false);
            this.fireEvent('change', this, v);
            if(changeComplete){
                this.fireEvent('changecomplete', this, v);
            }
        }
    },

	
    translateValue : function(v) {
        var ratio = this.getRatio();
        return (v * ratio)-(this.minValue * ratio)-this.halfThumb;
    },

	reverseValue : function(pos){
        var ratio = this.getRatio();
        return (pos+this.halfThumb+(this.minValue * ratio))/ratio;
    },

	
    moveThumb: function(v, animate){
    },

    resizeThumb: function(v, animate){
            this.thumb.setWidth(this.value*16, animate);
    },
	
    focus : function(){
        this.focusEl.focus(10);
    },

	
    onBeforeDragStart : function(e){
        return !this.disabled;
    },

	
    onDragStart: function(e){
        this.thumb.addClass('x-starslider-thumb-drag');
        this.dragging = true;
        this.dragStartValue = this.value;
        this.fireEvent('dragstart', this, e);
    },

	
    onDrag: function(e){
        var pos = this.innerEl.translatePoints(this.tracker.getXY());
        this.setValue(Math.round(this.reverseValue(pos.left)), false);
        this.fireEvent('drag', this, e);
    },
	
	
    onDragEnd: function(e) {
        this.thumb.removeClass('x-starslider-thumb-drag');
        this.dragging = false;
        this.fireEvent('dragend', this, e);
        if(this.dragStartValue != this.value){
            this.fireEvent('changecomplete', this, this.value);
        }
    },


    onResize : function(w, h){
        this.innerEl.setWidth(w - (this.el.getPadding('l') + this.endEl.getPadding('r')));
        this.syncThumb();
    },
    
    
    syncThumb : function(){
        if(this.rendered){
            this.moveThumb(this.translateValue(this.value));
        }
    },


    getValue : function(){
        return this.value;
    }
});
Ext.reg('starslider', Ext.StarSlider);



Ext.form.StarRate = Ext.extend(Ext.form.Field, {
     defaultAutoCreate:{tag:'input', type:'hidden'}
    ,initComponent:function() {
        Ext.form.StarRate.superclass.initComponent.call(this);
    }
    ,onRender:function(ct, position) {

        Ext.form.StarRate.superclass.onRender.call(this, ct, position);

	Ext.apply(this.starConfig, {
		formElement: this.el.dom,
		listeners: {
			change: function(s, v) {
				this.formElement.value = v
			}
		}	
	})
        this.star = new Ext.StarSlider(this.starConfig);

        var t;
	    t = Ext.DomHelper.append(ct, {tag:'table',style:'border-collapse:collapse',children:[
		{tag:'tr',children:[
		    {tag:'td',style:'padding-right:4px', cls: 'x-form-element'},{tag:'td', cls: 'x-form-element'}
		]}
	    ]}, true);

        this.tableEl = t;
        this.wrap = t.wrap();


        this.star.render(t.child('td.x-form-element'));
        

        if(Ext.isIE && Ext.isStrict) {
            t.select('input').applyStyles({top:0});
        }

        this.star.el.swallowEvent(['keydown', 'keypress']);

        this.el.dom.name = this.hiddenName || this.name || this.id;

        this.star.el.dom.removeAttribute("name");

    }
});

Ext.reg('xstarrate', Ext.form.StarRate);

