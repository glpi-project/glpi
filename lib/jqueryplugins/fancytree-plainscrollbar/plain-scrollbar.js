/*!
 * PlainScrollbar JavaScript Library v1.0.0-rc.0
 * https://github.com/ewya/PlainScrollbar
 *
 * Copyright Kay Schewe <https://www.kayschewe.de>
 * Released under the MIT license
 * https://github.com/ewya/PlainScrollbar/blob/master/LICENSE
 */

/**
 * PlainScrollbar
 * @author Kay Schewe
 * @copyright 2019 - present
 * @param {object} customConfiguration
 * @constructor
 */
export default function PlainScrollbar(customConfiguration) {

	'use strict';

	var arrowElements = null,
		configuration = null,
		defaultConfiguration = {
			/**
			 * Configure that the scrollbar is always visible.
			 * @property {boolean} [alwaysVisible=true]
			 */
			alwaysVisible: true,

			/**
			 * Configure that the scrollbar has arrows on each end.
			 * An arrow click will move the slider by one item backward or forward.
			 * @property {boolean} [arrows=false]
			 */
			arrows: false,

			/**
			 * Configure the enabled state of the scrollbar.
			 * @property {boolean} [enabled=true]
			 */
			enabled: true,

			/**
			 * Configure that a click on the slider area will move the slider by the numberOfItems.visible backward
			 * or forward.
			 * @property {boolean} [movePageByPageOnAreaClick=true]
			 */
			movePageByPageOnAreaClick: true,

			/**
			 * Configure the number of items that should be considered.
			 * @property {{start: string|number, total: string|number, visible: string|number}} numberOfItems
			 */
			numberOfItems: {
				start: 0,
				total: 0,
				visible: 0
			},

			/**
			 * Configure the callback that should to be called if the scrollbar will change the scrollable state (i.e.
			 * can or cannot be scrolled). An "scrollable" event object is the sole callback function parameter.
			 * Parameter scrollable {{orientation: configuration.orientation, before: {boolean}, current: isScrollable}}.
			 * @property {function} [onScrollable=null] Example: function(scrollable){// Do something...}
			 */
			onScrollable: null,

			/**
			 * Configure the callback that should to be called if the scrollbar will change the slider and therefore
			 * the numberOfItems.start. A copy of the current numberOfItems object is the sole callback function parameter.
			 * @property {function} [onSet=null] Example: function(numberOfItems){// Do something...}
			 */
			onSet: null,

			/**
			 * Configure the scrollbar element, that must be a html5 container element (e.g. <div/>).
			 * @property {HTMLElement} scrollbarElement
			 */
			scrollbarElement: null,

			/**
			 * Configure the minimal size of the slider by px.
			 * @property {number} [sliderMinSize=20]
			 */
			sliderMinSize: 20,

			/**
			 * Configure the wheel speed factor.
			 * @property {number} [wheelSpeed=2]
			 */
			wheelSpeed: 2
		},
		eventTimeout = null,
		isEnabled = null,
		isScrollable = null,
		isSliderDrag = false,
		isWheel = null,
		maxAttribute = null,
		orientations = ['horizontal', 'vertical'],
		scrollbarElement = null,
		scrollbarElementDocument = null,
		scrollbarElementWindow = null,
		sliderAreaElement = null,
		sliderElement = null,
		sliderOffset = 0,
		valueAttribute = null;

	/** Private functions */

	/**
	 * Simple object extend function.
	 * @param obj
	 * @param src
	 * @returns {*}
	 */
	function extend(obj, src) {
		Object.keys(src).forEach(function (key) {
			obj[key] = src[key];
		});
		return obj;
	}

	/**
	 * Set the slider and execute the onSet callback.
	 * @param {{source: string, type: string, value: number}} data
	 * @param {boolean} [preventCallbackExecution=false] True prevents the execution of the callback (default is false).
	 */
	function setScrollbar(data, preventCallbackExecution) {

		// Test if data can be evaluated to set the slider.

		if (!data.hasOwnProperty('source')
			|| !data.hasOwnProperty('type')
			|| !data.hasOwnProperty('value')) {
			// TODO: types ;-)
			return false;
		}

		// Proceed setting slider value...

		var dataValue = (isNaN(data.value)) ? 0 : parseFloat(data.value),
			executeCallback = (preventCallbackExecution !== true);

		// First of all calculate newValue (sliderElement), isScrollable (scrollbarElement) and the new
		// configuration.numberOfItems.start.
		// Then apply the determined values and finally, the callbacks are executed if applicable.

		var sliderAreaSize = sliderAreaElement.getBoundingClientRect()[maxAttribute],
			itemSize = ((configuration.numberOfItems.total === 0)
				? 0
				: sliderAreaSize / configuration.numberOfItems.total),
			currentSliderSize = sliderElement.getBoundingClientRect()[maxAttribute],
			newSliderSize = Math.max(configuration.sliderMinSize, configuration.numberOfItems.visible * itemSize),
			currentValue = parseFloat(sliderElement.style[valueAttribute]),
			maxValue = Math.max(0, sliderAreaSize - newSliderSize),
			minValue = 0,
			newValue = currentValue;

		// Calculate newValue

		switch (data.type) {
			case 'delta':
				newValue = currentValue + dataValue;
				break;

			case 'x':
			case 'y':
				newValue = dataValue - ((isSliderDrag && sliderOffset) ? sliderOffset : 0);
				break;
		}

		if (newValue < minValue) {
			newValue = minValue;
		}
		if (newValue > maxValue) {
			newValue = maxValue;
		}

		// Calculate isScrollable
		isScrollable = (configuration.numberOfItems.total > configuration.numberOfItems.visible)
						&& (sliderAreaSize > configuration.sliderMinSize);

		// Calculate configuration.numberOfItems.start
		// Formula: start / value = (total - visible) / (sliderAreaSize - newSliderSize)
		configuration.numberOfItems.start = (0 === maxValue)
			? 0
			: (configuration.numberOfItems.total - configuration.numberOfItems.visible) / maxValue * newValue;

		// Apply sliderElement valueAttribute (left or top).
		if (currentValue !== newValue) {
			sliderElement.style[valueAttribute] = newValue + 'px';
		}

		// Apply sliderElement maxAttribute (height or width).
		if (currentSliderSize !== newSliderSize) {
			sliderElement.style[maxAttribute] = newSliderSize + 'px';
		}

		// Determine current (but soon replaced) scrollable state (isScrollableBefore).
		// If scrollable state changed, apply scrollbarElement data-scrollable attribute and execute the onScrollable
		// callback if applicable.

		var dataScrollable = scrollbarElement.getAttribute('data-scrollable'),
			isScrollableBefore = null;

		if ('true' === dataScrollable) {
			isScrollableBefore = true;
		} else if ('false' === dataScrollable) {
			isScrollableBefore = false;
		}

		if (isScrollableBefore !== isScrollable) {
			// Apply scrollbarElement data-scrollable attribute.
			scrollbarElement.setAttribute('data-scrollable', isScrollable);

			if (executeCallback) {
				// Execute the onScrollable callback and provide a scrollable change event object.
				if ('function' === typeof configuration.onScrollable) {
					configuration.onScrollable({
						orientation: configuration.orientation,
						before: isScrollableBefore,
						current: isScrollable
					});
				}
			}
		}

		// Execute the onSet callback if applicable.
		if (executeCallback) {
			// Execute the onSet callback and provide the configuration.numberOfItems as event object.
			if ('function' === typeof configuration.onSet) {
				configuration.onSet({
					start: configuration.numberOfItems.start,
					total: configuration.numberOfItems.total,
					visible: configuration.numberOfItems.visible
				});
			}
		}

		return true;
	}

	/**
	 * Calculate a data object by a (numberOfItems) start value that can be used for calling setScrollbar.
	 * @param {string|number} start
	 * @returns {{source: string, type: string, value: number}}
	 */
	function calculateDataFromStart(start) {

		start = parseFloat(start);

		var maxStart = configuration.numberOfItems.total - configuration.numberOfItems.visible,
			minStart = 0;

		if (start < minStart) {
			// TODO ?: Warn about inconsistency.
			start = minStart;
		}
		if (start > maxStart) {
			// TODO ?: Warn about inconsistency.
			start = maxStart;
		}

		var data = {
			source: 'start',
			type: '',
			value: 0
		};

		if ('horizontal' === configuration.orientation) {
			data.type = 'x';
		} else if ('vertical' === configuration.orientation) {
			data.type = 'y';
		}

		if (configuration.numberOfItems.total ===  0) {
			return data;
		}

		var sliderAreaSize = sliderAreaElement.getBoundingClientRect()[maxAttribute],
			itemSize = sliderAreaSize / configuration.numberOfItems.total,
			sliderSize = Math.max(configuration.sliderMinSize, configuration.numberOfItems.visible * itemSize),
			maxValue = Math.max(0, sliderAreaSize - sliderSize);

		// Formula: start / value = (total - visible) / (sliderAreaSize - sliderSize)

		data.value = (maxValue === 0)
			? 0
			: maxValue / (configuration.numberOfItems.total - configuration.numberOfItems.visible) * start;

		return data;
	}

	/**
	 * Calculate a data object by an event object that can be used for calling setScrollbar.
	 * @param {object} event
	 * @returns {{source: string, type: string, value: number}}
	 */
	function calculateDataFromEvent(event) {
		var data = {
			source: 'event',
			type: '',
			value: 0
		};

		switch (event.type) {
			case 'mousedown':
			case 'mousemove':
			case 'mouseup':
				if ('horizontal' === configuration.orientation) {
					data.type = 'x';
					data.value = event.pageX - sliderAreaElement.getBoundingClientRect()[valueAttribute];
				} else if ('vertical' === configuration.orientation) {
					data.type = 'y';
					data.value = event.pageY - sliderAreaElement.getBoundingClientRect()[valueAttribute];
				}
				break;

			case 'wheel':
				data.type = 'delta';

				if ('horizontal' === configuration.orientation) {
					data.value = (0 < event.deltaX) ? 1 : -1;
				} else if ('vertical' === configuration.orientation) {
					data.value = (0 < event.deltaY) ? 1 : -1;
				}

				data.value *= configuration.wheelSpeed;
				break;

			default:
				data.source = '';
		}

		return data;
	}

	/** scrollbarElement event listener */

	/**
	 * Handle scrollbar mouseenter event if scrollbar is enabled.
	 * @param {object} event
	 */
	function scrollbarMouseEnter(event) {
		if (!isEnabled) {
			return;
		}

		event.preventDefault();
		scrollbarElement.setAttribute('data-visible', true);
	}

	/**
	 * Handle scrollbar mouseleave event if scrollbar is enabled.
	 * @param {object} event
	 */
	function scrollbarMouseLeave(event) {
		if (!isEnabled) {
			return;
		}

		event.preventDefault();
		if (!isSliderDrag && !configuration.alwaysVisible) {
			scrollbarElement.setAttribute('data-visible', false);
		}
	}

	/** sliderAreaElement event listener */

	/**
	 * Handle slider area mousedown event if scrollbar is enabled and it's not a slider drag operation.
	 * @param {object} event
	 */
	function sliderAreaMouseDown(event) {
		if (!isEnabled || isSliderDrag) {
			return;
		}

		event.preventDefault();
		if (configuration.movePageByPageOnAreaClick) {
			var start = configuration.numberOfItems.start,
				visible = configuration.numberOfItems.visible,
				currentValue = parseFloat(sliderElement.style[valueAttribute]),
				value = currentValue;

			switch (configuration.orientation) {
				case 'horizontal':
					value = event.offsetX;
					break;

				case 'vertical':
					value = event.offsetY;
					break;
			}

			if (value < currentValue) {
				start -= visible;
			}
			if (value > currentValue) {
				start += visible;
			}

			setScrollbar(calculateDataFromStart(start), false);
		} else {
			setScrollbar(calculateDataFromEvent(event), false);
		}
	}

	/**
	 * Handle slider area mouseup event if it's a slider drag operation.
	 * @param {object} event
	 */
	function sliderAreaMouseUp(event) {
		if (!isSliderDrag) {
			return;
		}

		event.preventDefault();
		if (isEnabled) {
			setScrollbar(calculateDataFromEvent(event), false);
		}
		isSliderDrag = false;
	}

	/**
	 * Handle slider area wheel event if scrollbar is enabled and if it's not a slider drag operation.
	 * @param {object} event
	 */
	function sliderAreaWheel(event) {
		if (!isEnabled || isSliderDrag) {
			return;
		}

		event.preventDefault();
		clearTimeout(eventTimeout);
		isWheel = true;
		setScrollbar(calculateDataFromEvent(event), false);
		isWheel = false; //< TODO ?: Use timeout.
	}

	/** sliderElement event listener */

	/**
	 * Handle slider mousedown event if scrollbar is enabled.
	 * @param {object} event
	 */
	function sliderMouseDown(event) {
		if (!isEnabled) {
			return;
		}

		event.preventDefault();
		clearTimeout(eventTimeout);
		isSliderDrag = true;
		sliderOffset = 0;
		if ('horizontal' === configuration.orientation) {
			sliderOffset = event.offsetX;
		} else if ('vertical' === configuration.orientation) {
			sliderOffset = event.offsetY;
		}
	}

	/** window event listener */

	/**
	 * Handle window mousemove event if scrollbar is enabled and it's a slider drag operation.
	 * @param {object} event
	 */
	function windowMouseMove(event) {
		if (!isEnabled || !isSliderDrag) {
			return;
		}

		clearTimeout(eventTimeout);
		eventTimeout = setTimeout(function () {
			setScrollbar(calculateDataFromEvent(event), false);
		}, 1);
	}

	/**
	 * Handle window mouseup event if it's a slider drag operation.
	 * @param {object} event
	 */
	function windowMouseUp(event) {
		if (!isSliderDrag) {
			return;
		}

		clearTimeout(eventTimeout);
		if (!configuration.alwaysVisible) {
			scrollbarElement.setAttribute('data-visible', false);
		}
		if (isEnabled) {
			setScrollbar(calculateDataFromEvent(event), false);
		}
		isSliderDrag = false;
	}

	/** arrowElement event listener */

	/**
	 * Handle arrow (backward) click event if scrollbar is enabled.
	 * @param {object} event
	 */
	function arrowClickBackward(event) {
		if (!isEnabled) {
			return;
		}

		event.preventDefault();
		var start = configuration.numberOfItems.start - 1;
		setScrollbar(calculateDataFromStart(start), false);
	}

	/**
	 * Handle arrow (forward) click event if scrollbar is enabled.
	 * @param {object} event
	 */
	function arrowClickForward(event) {
		if (!isEnabled) {
			return;
		}

		event.preventDefault();
		var start = configuration.numberOfItems.start + 1;
		setScrollbar(calculateDataFromStart(start), false);
	}

	/** Public functions */

	/**
	 * Set the alwaysVisible state.
	 * @param {boolean} alwaysVisible Will be evaluated as boolean.
	 */
	this.alwaysVisible = function (alwaysVisible) {
		configuration.alwaysVisible = Boolean(alwaysVisible);
		scrollbarElement.setAttribute('data-visible', configuration.alwaysVisible);
	};

	/**
	 * Set the enabled state.
	 * @param {boolean} enabled Will be evaluated as boolean.
	 */
	this.enabled = function (enabled) {
		isEnabled = Boolean(enabled);
		scrollbarElement.setAttribute('data-enabled', isEnabled);
	};

	/**
	 * Return the enabled state.
	 * @returns {boolean}
	 */
	this.isEnabled = function () {
		return isEnabled;
	};

	/**
	 * Return the scrollable state.
	 * @returns {boolean}
	 */
	this.isScrollable = function () {
		return isScrollable;
	};

	/**
	 * Set the scrollbar. This includes adjusting the slider and executing the onSet callback (if not prevented).
	 * @param {object|string} mixed An event or numberOfItems object or a string that is evaluated as start number.
	 * @param {boolean} [preventCallbackExecution=false] True prevents the execution of the callback.
	 * @returns {boolean}
	 */
	this.set = function (mixed, preventCallbackExecution) {

		if (isSliderDrag || isWheel) {
			// Ignore external calls if currently an internal event source is processed.
			return false;
		}

		if (!isEnabled) {
			// Prevent callback execution but adjust the scrollbar.
			preventCallbackExecution = true;
		}

		// Determine if data can be calculated by object (event | numberOfItems) or can be evaluated as string (start).
		var data = null;

		switch (typeof mixed) {
			case 'object':
				// Test if mixed can be evaluated as an event object.
				data = calculateDataFromEvent(mixed);
				if ('event' === data.source) {
					return setScrollbar(data, preventCallbackExecution);
				}

				// Test if mixed can be evaluated as a numberOfItems object.
				if (mixed.hasOwnProperty('start') && !isNaN(mixed.start)
					&& mixed.hasOwnProperty('total') && !isNaN(mixed.total)
					&& mixed.hasOwnProperty('visible') && !isNaN(mixed.visible)) {

					configuration.numberOfItems = extend(configuration.numberOfItems, {
						start: mixed.start,
						total: mixed.total,
						visible: mixed.visible
					});
					data = calculateDataFromStart(configuration.numberOfItems.start);
					return setScrollbar(data, preventCallbackExecution);
				}
				break;

			case 'string':
				// Test if mixed can be evaluated as a start value.
				data = calculateDataFromStart(mixed);
				if ('start' === data.source) {
					return setScrollbar(data, preventCallbackExecution);
				}
				break;
		}

		return false;
	};

	/**
	 * Init the scrollbar.
	 */

	// Validate configuration

	if (!customConfiguration) {
		throw 'Missing customConfiguration!';
	}
	if (!customConfiguration.hasOwnProperty('scrollbarElement')
		|| !customConfiguration.scrollbarElement
		|| customConfiguration.scrollbarElement.hasOwnProperty('nodeName')) { //< TODO
		throw 'Missing valid configuration.scrollbarElement!';
	}
	if (!customConfiguration.hasOwnProperty('orientation')
		|| orientations.indexOf(customConfiguration.orientation) === -1) {
		throw 'Missing valid configuration.orientation!';
	}

	// Proceed to create the scrollbar...

	configuration = extend(defaultConfiguration, customConfiguration);
	scrollbarElement = configuration.scrollbarElement;
	scrollbarElementDocument = scrollbarElement.ownerDocument;

	var cssClasses = [
		'plain-scrollbar',
		'scrollbar-' + configuration.orientation
	];

	if ('horizontal' === configuration.orientation) {
		maxAttribute = 'width';
		valueAttribute = 'left';
	} else if ('vertical' === configuration.orientation) {
		maxAttribute = 'height';
		valueAttribute = 'top';
	}

	isEnabled = Boolean(configuration.enabled);

	scrollbarElement.setAttribute('data-enabled', isEnabled);
	scrollbarElement.setAttribute('data-scrollable', isScrollable);
	scrollbarElement.setAttribute('data-visible', Boolean(configuration.alwaysVisible));

	cssClasses.forEach(function (cssClass) {
		scrollbarElement.classList.add(cssClass);
	});

	// Create arrowElements if applicable.

	if (configuration.arrows) {
		scrollbarElement.classList.add('has-arrows');
		arrowElements = {
			'backward': scrollbarElementDocument.createElement('div'),
			'forward': scrollbarElementDocument.createElement('div')
		};

		Object.getOwnPropertyNames(arrowElements).forEach(function (name) {
			var cssClass = 'arrow-',
				eventListener = function () {
				};

			switch (name) {
				case 'backward':
					if ('horizontal' === configuration.orientation) {
						cssClass += 'left';
					} else if ('vertical' === configuration.orientation) {
						cssClass += 'up';
					}
					eventListener = arrowClickBackward;
					break;

				case 'forward':
					if ('horizontal' === configuration.orientation) {
						cssClass += 'right';
					} else if ('vertical' === configuration.orientation) {
						cssClass += 'down';
					}
					eventListener = arrowClickForward;
					break;

				default:
					cssClass += 'undefined';
			}

			var arrowElement = arrowElements[name];
			arrowElement.classList.add(cssClass);
			arrowElement.appendChild(scrollbarElementDocument.createElement('span'));
			arrowElement.addEventListener('mousedown', eventListener, false);
			scrollbarElement.appendChild(arrowElement);
		});
	}

	// Create SliderElement, sliderAreaElement, add scrollbarElementWindow event listener and set the scrollbar.

	sliderElement = scrollbarElementDocument.createElement('div');
	sliderElement.classList.add('slider');
	sliderElement.style[valueAttribute] = '0';
	sliderElement.style[maxAttribute] = '0';
	sliderElement.addEventListener('mousedown', sliderMouseDown, false);

	sliderAreaElement = scrollbarElementDocument.createElement('div');
	sliderAreaElement.classList.add('slider-area');
	sliderAreaElement.appendChild(sliderElement);
	sliderAreaElement.addEventListener('mousedown', sliderAreaMouseDown, false);
	sliderAreaElement.addEventListener('mouseup', sliderAreaMouseUp, false);
	sliderAreaElement.addEventListener('wheel', sliderAreaWheel, false);

	scrollbarElement.appendChild(sliderAreaElement);
	scrollbarElement.addEventListener('mouseenter', scrollbarMouseEnter, false);
	scrollbarElement.addEventListener('mouseleave', scrollbarMouseLeave, false);

	scrollbarElementWindow = scrollbarElementDocument.defaultView;
	scrollbarElementWindow.addEventListener('mousemove', windowMouseMove, false);
	scrollbarElementWindow.addEventListener('mouseup', windowMouseUp, false);

	setScrollbar(calculateDataFromStart(configuration.numberOfItems.start), false);
}
