/*!
 * Ext JS Library 3.3.1
 * Copyright(c) 2006-2010 Sencha Inc.
 * licensing@sencha.com
 * http://www.sencha.com/license
 */
Ext.ns('Ext.ux');
/**
 * @class Ext.ux.TabScrollerMenu
 * @extends Object 
 * Plugin (ptype = 'tabscrollermenu') for adding a tab scroller menu to tabs.
 * @constructor 
 * @param {Object} config Configuration options
 * @ptype tabscrollermenu
 */
Ext.ux.TabScrollerMenu =  Ext.extend(Object, {
    /**
     * @cfg {Number} pageSize How many items to allow per submenu.
     */
	pageSize       : 10,
    /**
     * @cfg {Number} maxText How long should the title of each {@link Ext.menu.Item} be.
     */
	maxText        : 15,
    /**
     * @cfg {String} menuPrefixText Text to prefix the submenus.
     */    
	menuPrefixText : 'Items',
	constructor    : function(config) {
		config = config || {};
		Ext.apply(this, config);
	},
    //private
	init : function(tabPanel) {
		Ext.apply(tabPanel, this.parentOverrides);
		
		tabPanel.tabScrollerMenu = this;
		var thisRef = this;
		
		tabPanel.on({
			render : {
				scope  : tabPanel,
				single : true,
				fn     : function() { 
					var newFn = tabPanel.createScrollers.createSequence(thisRef.createPanelsMenu, this);
					tabPanel.createScrollers = newFn;
				}
			}
		});
	},
	// private && sequeneced
	createPanelsMenu : function() {
		var h = this.stripWrap.dom.offsetHeight;
		
		//move the right menu item to the left 18px
		var rtScrBtn = this.header.dom.firstChild;
		Ext.fly(rtScrBtn).applyStyles({
			right : '18px'
		});
		
		var stripWrap = Ext.get(this.strip.dom.parentNode);
		stripWrap.applyStyles({
			 'margin-right' : '36px'
		});
		
		// Add the new righthand menu
		var scrollMenu = this.header.insertFirst({
			cls:'x-tab-tabmenu-right'
		});
		scrollMenu.setHeight(h);
		scrollMenu.addClassOnOver('x-tab-tabmenu-over');
		scrollMenu.on('click', this.showTabsMenu, this);	
		
		this.scrollLeft.show = this.scrollLeft.show.createSequence(function() {
			scrollMenu.show();												 						 
		});
		
		this.scrollLeft.hide = this.scrollLeft.hide.createSequence(function() {
			scrollMenu.hide();								
		});
		
	},
    /**
     * Returns an the current page size (this.pageSize);
     * @return {Number} this.pageSize The current page size.
     */
	getPageSize : function() {
		return this.pageSize;
	},
    /**
     * Sets the number of menu items per submenu "page size".
     * @param {Number} pageSize The page size
     */
    setPageSize : function(pageSize) {
		this.pageSize = pageSize;
	},
    /**
     * Returns the current maxText length;
     * @return {Number} this.maxText The current max text length.
     */
    getMaxText : function() {
		return this.maxText;
	},
    /**
     * Sets the maximum text size for each menu item.
     * @param {Number} t The max text per each menu item.
     */
    setMaxText : function(t) {
		this.maxText = t;
	},
    /**
     * Returns the current menu prefix text String.;
     * @return {String} this.menuPrefixText The current menu prefix text.
     */
	getMenuPrefixText : function() {
		return this.menuPrefixText;
	},
    /**
     * Sets the menu prefix text String.
     * @param {String} t The menu prefix text.
     */    
	setMenuPrefixText : function(t) {
		this.menuPrefixText = t;
	},
	// private && applied to the tab panel itself.
	parentOverrides : {
		// all execute within the scope of the tab panel
		// private	
		showTabsMenu : function(e) {		
			if  (this.tabsMenu) {
				this.tabsMenu.destroy();
                this.un('destroy', this.tabsMenu.destroy, this.tabsMenu);
                this.tabsMenu = null;
			}
            this.tabsMenu =  new Ext.menu.Menu();
            this.on('destroy', this.tabsMenu.destroy, this.tabsMenu);

            this.generateTabMenuItems();

            var target = Ext.get(e.getTarget());
			var xy     = target.getXY();
//
			//Y param + 24 pixels
			xy[1] += 24;
			
			this.tabsMenu.showAt(xy);
		},
		// private	
		generateTabMenuItems : function() {
			var curActive  = this.getActiveTab();
			var totalItems = this.items.getCount();
			var pageSize   = this.tabScrollerMenu.getPageSize();
			
			
			if (totalItems > pageSize)  {
				var numSubMenus = Math.floor(totalItems / pageSize);
				var remainder   = totalItems % pageSize;
				
				// Loop through all of the items and create submenus in chunks of 10
				for (var i = 0 ; i < numSubMenus; i++) {
					var curPage = (i + 1) * pageSize;
					var menuItems = [];
					
					
					for (var x = 0; x < pageSize; x++) {				
						index = x + curPage - pageSize;
						var item = this.items.get(index);
						menuItems.push(this.autoGenMenuItem(item));
					}
					
					this.tabsMenu.add({
						text : this.tabScrollerMenu.getMenuPrefixText() + ' '  + (curPage - pageSize + 1) + ' - ' + curPage,
						menu : menuItems
					});
					
				}
				// remaining items
				if (remainder > 0) {
					var start = numSubMenus * pageSize;
					menuItems = [];
					for (var i = start ; i < totalItems; i ++ ) {					
						var item = this.items.get(i);
						menuItems.push(this.autoGenMenuItem(item));
					}
					
					this.tabsMenu.add({
						text : this.tabScrollerMenu.menuPrefixText  + ' ' + (start + 1) + ' - ' + (start + menuItems.length),
						menu : menuItems
					});

				}
			}
			else {
				this.items.each(function(item) {
// 					if (item.id != curActive.id) {
                        this.tabsMenu.add(this.autoGenMenuItem(item));
// 					}
				}, this);
			}
		},
		// private
		autoGenMenuItem : function(item) {
			var maxText = this.tabScrollerMenu.getMaxText();
			var text    = Ext.util.Format.ellipsis(item.title, maxText);
			
			return {
				text      : text,
				handler   : this.showTabFromMenu,
				scope     : this,
				disabled  : item.disabled,
				tabToShow : item,
				iconCls   : item.iconCls
			}
		
		},
		// private
		showTabFromMenu : function(menuItem) {
			this.setActiveTab(menuItem.tabToShow);
		}	
	}	
});

Ext.preg('tabscrollermenu', Ext.ux.TabScrollerMenu);
