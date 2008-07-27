// vim: ts=4:sw=4:nu:fdc=2:nospell
/**
 * Ext.ux.form.XDateField - Date field that supports submitFormat
 *
 * @author  Ing. Jozef Sakalos
 * @version $Id: Ext.ux.form.XDateField.js 259 2008-05-14 15:21:42Z jozo $
 *
 * @license Ext.ux.grid.XDateField is licensed under the terms of
 * the Open Source LGPL 3.0 license.  Commercial use is permitted to the extent
 * that the code/component(s) do NOT become part of another Open Source or Commercially
 * licensed development library or toolkit without explicit permission.
 * 
 * License details: http://www.gnu.org/licenses/lgpl.html
 */

/*global Ext */

Ext.ns('Ext.ux.form');

/**
  * @class Ext.ux.form.XDateField
  * @extends Ext.form.DateField
  */
Ext.ux.form.XDateField = Ext.extend(Ext.form.DateField, {
     submitFormat:'Y-m-d'
    ,onRender:function() {

        // call parent
        Ext.ux.form.XDateField.superclass.onRender.apply(this, arguments);

        this.hiddenField = this.el.insertSibling({
             tag:'input'
            ,type:'hidden'
            ,name:this.name
            ,value:this.formatHiddenDate(this.parseDate(this.value))
        });
        this.hiddenName = this.name; // otherwise field is not found by BasicForm::findField
        this.el.dom.removeAttribute('name');
        this.el.on({
             keyup:{scope:this, fn:this.updateHidden}
            ,blur:{scope:this, fn:this.updateHidden}
        });

        this.setValue = this.setValue.createSequence(this.updateHidden);

    } // eo function onRender

    ,onDisable: function(){
        // call parent
        Ext.ux.form.XDateField.superclass.onDisable.apply(this, arguments);
        if(this.hiddenField) {
            this.hiddenField.dom.setAttribute('disabled','disabled');
        }
    } // of function onDisable

    ,onEnable: function(){
        // call parent
        Ext.ux.form.XDateField.superclass.onEnable.apply(this, arguments);
        if(this.hiddenField) {
            this.hiddenField.dom.removeAttribute('disabled');
        }
    } // eo function onEnable

    ,formatHiddenDate : function(date){
	// GLPI correction
	return Ext.isDate(date) ? Ext.util.Format.date(date, this.submitFormat) : 'NULL';
        //return Ext.isDate(date) ? Ext.util.Format.date(date, this.submitFormat) : date;
    }

    ,updateHidden:function() {
        this.hiddenField.dom.value = this.formatHiddenDate(this.getValue());
    } // eo function updateHidden

}); // end of extend

// register xtype
Ext.reg('xdatefield', Ext.ux.form.XDateField);

// eof 
