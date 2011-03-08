Ext.ns('Ext.ux.form');
// Create for use other display (inline)
Ext.ux.form.SpanComboBox = Ext.extend(Ext.form.ComboBox, {


	initComponent:function() {
	        // call parent initComponent
        	Ext.ux.form.SpanComboBox.superclass.initComponent.call(this);
	}
	,onRender : function(ct, position){

		// don't run more than once
		if(this.isRendered) {
		return;
		}
	
		// render underlying hidden field
		Ext.ux.form.SpanComboBox.superclass.onRender.call(this, ct, position);

		var wrap = this.el.up('div.x-form-field-wrap');
		this.wrap.applyStyles({display:'inline'});
	}

});

Ext.reg('spancombobox', Ext.ux.form.SpanComboBox);