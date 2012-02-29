/*

This file does a lot of stuff relating to overlays...

It provides callback-based *overlay*-driven substitutions for the standard browser windowing API...
 - alert
 - prompt
 - confirm
 - open (known as popups)
 - showModalDialog
A term we are using for these kinds of 'overlay' is '(faux) modal window'.

It provides a generic function to open a link as an overlay.

It provides a function to open an image link as a 'lightbox' (we use the term lightbox exclusively to refer to images in an overlay).

*/

var overlay_zIndex=1000;

function open_link_as_overlay(ob,width,height)
{
	{+START,IF,{$NOT,{$VALUE_OPTION,no_faux_popups}}}
		if (!width) width=800;
		if (!height) height=520;
		var url=(typeof ob.href=='undefined')?ob.action:ob.href;
		faux_open(url+((url.indexOf('?')==-1)?'?':'&')+'wide_high=1',null,'width='+width+';height='+height,'_top');
		return false;
	{+END}

	return true;
}

{+START,IF,{$NOT,{$VALUE_OPTION,no_faux_popups}}}
	function open_image_into_lightbox(a)
	{
		// Set up overlay for Lightbox
		var lightbox_code='<p class="ajax_tree_list_loading"><img id="lightbox_image" class="inline_image_2" src="{$IMG*,bottom/loading}" /></p><p class="community_block_tagline">[ <a href="'+escape_html(a.href)+'" target="_blank" title="{$STRIP_TAGS;,{!SEE_FULL_IMAGE}} {!LINK_NEW_WINDOW}">{!SEE_FULL_IMAGE;}</a> ]</p>';

		// Show overlay
		var myLightbox = {
			type: "lightbox",
			text: lightbox_code,
			yes_button: "{!INPUTSYSTEM_CLOSE#}",
			width: 450,
			height: 300
		};
		var modal=new ModalWindow();
		modal.open(myLightbox);

		// Load proper image
		window.setTimeout(function() { // Defer execution until the HTML was parsed
			var img=modal.topWindow.document.createElement('img');
			img.onload=function()
			{
				var real_width=img.width;
				var width=real_width;
				var real_height=img.height;
				var height=real_height;

				// Might need to rescale using some maths, if natural size is too big
				var max_width=modal.topWindow.getWindowWidth()-20;
				var max_height=modal.topWindow.getWindowHeight()-180;
				if (width>max_width)
				{
					width=max_width;
					height=window.parseInt(max_width*real_height/real_width-1);
				}
				if (height>max_height)
				{
					width=window.parseInt(max_height*real_width/real_height-1);
					height=max_height;
				}
				modal.resetDimensions(width,height);

				var sup=modal.topWindow.document.getElementById('lightbox_image').parentNode;
				img.className='no_alpha';
				img.width=width;
				img.height=height;
				sup.removeChild(sup.childNodes[0]);
				sup.appendChild(img);
				sup.className='';
				sup.style.textAlign='center';
				sup.style.overflow='hidden';
			}
			img.src=a.href;
		},0);
	}
{+END}

function fauxmodal_confirm(question,callback,title)
{
	if (!title) title='{!Q_SURE;}';

	{+START,IF,{$NOT,{$VALUE_OPTION,no_faux_popups}}}
		var myConfirm = {
			type: "confirm",
			text: escape_html(question),
			yes_button: "{!YES#}",
			no_button: "{!NO#}",
			title: title,
			yes: function() {
				callback(true);
			},
			no: function() {
				callback(false);
			},
			width: 450
		};
		new ModalWindow().open(myConfirm);
	{+END}

	{+START,IF,{$VALUE_OPTION,no_faux_popups}}
		callback(window.confirm(question));
	{+END}
}

function fauxmodal_alert(notice,callback,title)
{
	if (!callback) callback=function() {};

	if (!title) title='{!MESSAGE;}';

	{+START,IF,{$NOT,{$VALUE_OPTION,no_faux_popups}}}
		var myAlert = {
			type: "alert",
			text: escape_html(notice),
			yes_button: "{!INPUTSYSTEM_OK#}",
			width: 600,
			yes: callback,
			title: title
		};
		new ModalWindow().open(myAlert);
	{+END}

	{+START,IF,{$VALUE_OPTION,no_faux_popups}}
		window.alert(notice);
		callback();
	{+END}
}

function fauxmodal_prompt(question,defaultValue,callback,title,input_type)
{
	{+START,IF,{$NOT,{$VALUE_OPTION,no_faux_popups}}}
		var myPrompt = {
			type: "prompt",
			text: escape_html(question),
			yes_button: "{!INPUTSYSTEM_OK#}",
			cancel_button: "{!INPUTSYSTEM_CANCEL#}",
			defaultValue: defaultValue,
			title: title,
			yes: function(value) {
				callback(value);
			},
			cancel: function() {
				callback(null);
			},
			width: 450
		};
		if (input_type) myPrompt.input_type=input_type;
		new ModalWindow().open(myPrompt);
	{+END}

	{+START,IF,{$VALUE_OPTION,no_faux_popups}}
		callback(window.prompt(question,defaultValue));
	{+END}
}

function faux_showModalDialog(url,name,options,callback,target,cancel_text)
{
	if (!callback) callback=function() {};

	{+START,IF,{$NOT,{$VALUE_OPTION,no_faux_popups}}}
		var width=null,height=null,scrollbars=null,unadorned=null;

		if (!cancel_text) cancel_text="{!INPUTSYSTEM_CANCEL#}";

		if (options)
		{
			var parts=options.split(/[;,]/g);
			for (var i=0;i<parts.length;i++)
			{
				var bits=parts[i].split('=');
				if (typeof bits[1]!='undefined')
				{
					if ((bits[0]=='dialogWidth') || (bits[0]=='width'))
						width=window.parseInt(bits[1].replace(/px$/,''));
					if ((bits[0]=='dialogHeight') || (bits[0]=='height'))
					{
						if (bits[1]=='100%')
						{
							height = getWindowHeight() - 200;
						} else
						{
							height=window.parseInt(bits[1].replace(/px$/,''));
						}
					}
					if (((bits[0]=='resizable') || (bits[0]=='scrollbars')) && scrollbars!==true)
						scrollbars=((bits[1]=='yes') || (bits[1]=='1'))/*if either resizable or scrollbars set we go for scrollbars*/;
					if (bits[0]=='unadorned') unadorned=((bits[1]=='yes') || (bits[1]=='1'));
				}
			}
		}

		var myFrame = {
			type: "iframe",
			finished: function(value) {
				callback(value);
			},
			name: name,
			width: width,
			height: height,
			scrollbars: scrollbars,
			href: url
		};
		myFrame.cancel_button=(unadorned!==true)?cancel_text:null;
		if (target) myFrame.target=target;
		new ModalWindow().open(myFrame);
	{+END}

	{+START,IF,{$VALUE_OPTION,no_faux_popups}}
		var timer=new Date().getTime();
		try
		{
			var result=window.showModalDialog(url,name,options);
		}
		catch (e) {}; // IE gives "Access is denied" if popup was blocked, due to var result assignment to non-real window
		var timer_now=new Date().getTime();
		if (timer_now-100>timer) // Not popup blocked
		{
			if ((typeof result=="undefined") || (result===null))
			{
				callback(null);
			} else
			{
				callback(result);
			}
		}
	{+END}
}

function faux_open(url,name,options,target,cancel_text)
{
	if (!cancel_text) cancel_text="{!INPUTSYSTEM_CLOSE#}";

	{+START,IF,{$NOT,{$VALUE_OPTION,no_faux_popups}}}
		faux_showModalDialog(url,name,options,null,target,cancel_text);
	{+END}

	{+START,IF,{$VALUE_OPTION,no_faux_popups}}
		window.open(url,name,options);
	{+END}
}

{+START,IF,{$NOT,{$VALUE_OPTION,no_faux_popups}}}
/*
Originally...

Script: modalwindow.js
	ModalWindow - Simple javascript popup overlay to replace builtin alert, prompt and confirm, and more.

License:
	PHP-style license.

Copyright:
	Copyright (c) 2009 [Kieron Wilson](http://kd3sign.co.uk/).

Code & Documentation:
	http://kd3sign.co.uk/examples/modalwindow/

HEAVILY Modified by ocProducts for ocPortal.

*/

function ModalWindow()
{
	return {

		box: null,
		returnValue: null,
		topWindow: null,

		open: function() {
			var options = arguments[0] || {};
			var defaults = {
				'type': "alert",
				'opacity': "0.5",
				'width': null,
				'height': 'auto',
				'title': "",
				'text': "",
				'yes_button': "{!YES#}",
				'no_button': "{!NO#}",
				'cancel_button': "{!INPUTSYSTEM_CANCEL#}",
				'yes': null,
				'no': null,
				'finished': null,
				'cancel': null,
				'href': null,
				'scrollbars': null,
				'defaultValue': null,
				'target': '_self',
				'input_type': 'text'
			};

			this.topWindow=window.top;
			if (this.topWindow.opener) this.topWindow=this.topWindow.opener;
			this.topWindow=this.topWindow.top;

			for(var key in defaults) {
				this[key] = (typeof options[key] != "undefined") ? options[key] : defaults[key] ;
			}

			this.close(this.topWindow);
			this.initOverlay();
			this.initBox();
		},

		close: function(win) {
			var bi=this.topWindow.document.getElementById('body_inner');

			if(this.box) {
				this.remove(this.box, win);
				this.box = null;

				if (bi)
				{
					this.topWindow.setOpacity(bi,1.0);
				}

				if (this.type == "prompt") this.removeEvent(bi.parentNode, "click", this.clickout_cancel);
				if (this.type == "iframe") this.removeEvent(bi.parentNode, "click", this.clickout_finished);
				if (this.type == "alert" || this.type == "lightbox") this.removeEvent(bi.parentNode, "click", this.clickout_yes);
				this.removeEvent(document, "keyup", this.keyup);
			}
			this.opened = false;
		},

		option: function(method) {
			var win = this.topWindow; // The below call may end up killing our window reference (for nested alerts), so we need to grab it first
			if(this[ method ]) {
				if(this.type == "prompt") {
					this[ method ](this.input.value);
				}
				else if(this.type == "iframe") {
					this[ method ](this.returnValue);
				}
				else this[ method ]();
			}
			this.close(win);
		},

		resetDimensions: function(width, height) { // Don't re-call this for an iframe-based overlay, doesn't work retro-actively on the iframe size (but CSS sized inards are fine)
			var dim = this.getPageSize();

			var boxWidth = ((width) ? (width + 8) : (dim.pageWidth / 4))  + "px";
			var extra_box_height = (this.type == "iframe" ) ? 160 : 120;
			if (this.cancel_button === null) extra_box_height = 0;
			var boxHeight = (typeof height == "string" || height === null || this.type == "iframe") ? "auto" : (height + extra_box_height) + "px" ;

			var boxPosVCentre = (typeof height == "string" || height === null || this.type == "iframe") ? ((this.type == "iframe") ? 20 : 150) : ((dim.windowHeight / 2.5) - (parseInt(boxHeight) / 2)) ;
			if (boxPosVCentre < 20) boxPosVCentre = 20;
			var boxPosHCentre = ((dim.pageWidth / 2) - (parseInt(boxWidth) / 2));

			var boxPosTop = (/*getWindowScrollY() + */boxPosVCentre) + "px" ;
			var boxPosLeft = boxPosHCentre + "px";

			this.width = width;
			this.height = height;

			this.box.style.width = boxWidth;
			this.box.style.height = boxHeight;

			this.box.style.top = boxPosTop;
			this.box.style.left = boxPosLeft;
		},

		initBox: function() {
			this.box = this.element("div", {
				'class': 'medborder medborder_box overlay',
				'role': 'dialog',
				'styles' : {
					'position': browser_matches('ie_old')?"absolute":"fixed",
					'zIndex': this.topWindow.overlay_zIndex++,
					'overflow': (this.type == "iframe") ? "auto" : "hidden",
					'borderRadius': "15px"
				}
			});

			this.resetDimensions(this.width,this.height);

			this.inject(this.box);

			var container = this.element("div", {
				'class': "standardbox_main_classic",
				'styles' : {
					'width': "auto",
					'height': "auto"
				}
			});

			var overlay_header = null;
			if (this.title != '' || this.type == "iframe") {
				overlay_header = this.element("h3", {
					'html': this.title,
					'styles' : {
						'display': (this.title == "") ? "none" : "block"
					}
				});
				container.appendChild(overlay_header);
			}

			if (this.text != '') {
				if (this.type == "prompt")
				{
					var p = this.element("p");
					p.appendChild(this.element("label", {
						'for': "overlay_prompt",
						'html': this.text
					}));
					container.appendChild(p);
				} else
				{
					container.appendChild(this.element("p", {
						'html': this.text
					}));
				}
			}

			var buttonContainer = this.element("div", {
				'class': "proceed_button"
			});

			var _this = this;

			this.clickout_cancel = function() {
				_this.option('cancel');
			};

			this.clickout_finished = function() {
				_this.option('finished');
			};

			this.clickout_yes = function() {
				_this.option('yes');
			};

			this.keyup = function(e) {
				if(!e) e = window.event ;
				var keyCode = (e) ? (e.which || e.keyCode) : null ;

				if(keyCode == 13) {
					_this.option('yes');
				}
			};

			this.addEvent( this.box, "click", function(e) { cancelBubbling(e); } );

			switch(this.type) {
				case "iframe":
					var iframe = this.element("iframe", {
						'frameBorder': "0",
						'scrolling': "no",
						'title': "",
						'name': "overlay_iframe",
						'id': "overlay_iframe",
						'src': this.href,
						'allowTransparency': "true",
						'styles' : {
							'width': this.width?(this.width+'px'):"100%",
							'height': this.height?(this.height+'px'):"50%",
							'background': "transparent"
						}
					});

					container.appendChild(iframe);

					if (this.cancel_button)
					{
						var button=this.element("button", {
							'html': this.cancel_button,
							'class': "button_pageitem"
						});
						this.addEvent( button, "click", function() { _this.option('finished'); } );
						buttonContainer.appendChild(button);
						container.appendChild(this.element("hr", { 'class': 'spaced_rule' } ));
						container.appendChild(buttonContainer);
					}
					var bi=this.topWindow.document.getElementById('body_inner');
					if (bi)
						window.setTimeout(function() { _this.addEvent( bi.parentNode, "click", _this.clickout_finished); }, 1000);

					this.addEvent( iframe, "load", function() {
						if (typeof iframe.contentWindow.document.getElementsByTagName('h1')[0] == 'undefined' && typeof iframe.contentWindow.document.getElementsByTagName('h2')[0] == 'undefined')
						{
							setInnerHTML(overlay_header,escape_html(iframe.contentWindow.document.title));
							overlay_header.style.display='block';
						}
					} );

					// Fiddle it, to behave like a popup would
					var name=this.name;
					var makeFrameLikePopup=function() {
						if ((iframe) && (iframe.contentWindow) && (iframe.contentWindow.document) && (iframe.contentWindow.document.body))
						{
							iframe.contentWindow.document.body.style.background='transparent';

							if (iframe.contentWindow.document.body.className.indexOf('overlay')==-1)
								iframe.contentWindow.document.body.className+=' overlay';

							// Allow scrolling, if we want it
							iframe.scrolling=(_this.scrollbars === false)?"no":"auto";

							// Remove fixed width
							var body_inner=iframe.contentWindow.document.getElementById('body_inner');
							if (body_inner) body_inner.id='';

							// Remove main_website marker
							var main_website=iframe.contentWindow.document.getElementById('main_website');
							if (main_website) main_website.id='';

							// Remove popup spacing
							var popup_spacer=iframe.contentWindow.document.getElementById('popup_spacer');
							if (popup_spacer) popup_spacer.id='';

							iframe.contentWindow.opener = window;
							var bases=iframe.contentWindow.document.getElementsByTagName('base');
							var baseElement;
							if (!bases[0])
							{
								baseElement=iframe.contentWindow.document.createElement('base');
								if (iframe.contentWindow.document)
								{
									var heads=iframe.contentWindow.document.getElementsByTagName('head');
									if (heads[0])
									{
										heads[0].appendChild(baseElement);
									}
								}
							} else
							{
								baseElement=bases[0];
							}
							baseElement.target=_this.target;

							if (name && iframe.contentWindow.name != name) iframe.contentWindow.name=name;

							if (typeof iframe.contentWindow.faux_close=='undefined')
							{
								iframe.contentWindow.faux_close=function() {
									if (iframe && iframe.contentWindow && typeof iframe.contentWindow.returnValue!='undefined')
										_this.returnValue=iframe.contentWindow.returnValue;
									_this.option('finished');
								};
							}
						}
					};
					window.setTimeout(function() { illustrateFrameLoad(iframe,'overlay_iframe'); makeFrameLikePopup(); },0);
					window.setInterval(makeFrameLikePopup,100); // In case internal nav changes
					break;

				case "lightbox":
				case "alert":
					if(this.yes != false) {
						var button=this.element("button", {
							'html': this.yes_button,
							'class': "button_pageitem"
						});
						this.addEvent( button, "click", function() { _this.option('yes'); } );
						var bi=this.topWindow.document.getElementById('body_inner');
						if (bi)
							window.setTimeout(function() { _this.addEvent( bi.parentNode, "click", _this.clickout_yes); }, 1000);
						buttonContainer.appendChild(button);
						container.appendChild(buttonContainer);
					}
					break;

				case "confirm":
					var button=this.element("button", {
						'html': this.yes_button,
						'class': "button_pageitem",
						'style': "font-weight: bold;"
					});
					this.addEvent( button, "click", function() { _this.option('yes'); } );
					buttonContainer.appendChild(button);
					var button=this.element("button", {
						'html': this.no_button,
						'class': "button_pageitem"
					});
					this.addEvent( button, "click", function() { _this.option('no'); } );
					buttonContainer.appendChild(button);

					container.appendChild(buttonContainer);
					break;

				case "prompt":
					this.input = this.element("input", {
						'name': "prompt",
						'id': "overlay_prompt",
						'type': this.input_type,
						'size': "40",
						'class': "wide_field",
						'value': this.defaultValue
					});
					var input_wrap = this.element("div", {
						'class': "constrain_field"
					});
					input_wrap.appendChild(this.input);
					container.appendChild(input_wrap);

					if(this.yes) {
						var button=this.element("button", {
							'html': this.yes_button,
							'class': "button_pageitem",
							'style': "font-weight: bold;"
						});
						this.addEvent( button, "click", function() { _this.option('yes'); } );
						buttonContainer.appendChild(button);
					}
					var button=this.element("button", {
						'html': this.cancel_button,
						'class': "button_pageitem"
					});
					this.addEvent( button, "click", function() { _this.option('cancel'); } );
					var bi=this.topWindow.document.getElementById('body_inner');
					if (bi)
						window.setTimeout(function() { _this.addEvent( bi.parentNode, "click", _this.clickout_cancel); }, 1000);
					buttonContainer.appendChild(button);

					container.appendChild(buttonContainer);
					break;
			}

			this.box.appendChild(container);

			if(this.input) this.input.focus();
			else if (typeof this.box.getElementsByTagName('button')[0]!='undefined') this.box.getElementsByTagName('button')[0].focus();

			if(this.yes || this.yes != false) this.addEvent(document, "keyup", this.keyup );
		},

		initOverlay: function() {
			var bi=this.topWindow.document.getElementById('body_inner');
			if (bi)
			{
				if (typeof window.nereidFade!='undefined')
				{
					this.topWindow.setOpacity(bi,1.0);
					nereidFade(bi,30,30,-5);
				} else
				{
					this.topWindow.setOpacity(bi,0.3);
				}
			}
		},

		inject: function(el) {
			this.topWindow.document.body.appendChild(el);
		},

		remove: function(el, win) {
			if (!win) win = this.topWindow;
			win.document.body.removeChild(el);
		},

		element: function() {
			var tag = arguments[0], options = arguments[1];
			var el = this.topWindow.document.createElement(tag);
			var attributes = {
				'html': 'innerHTML',
				'class': 'className',
				'for': 'htmlFor',
				'text': 'innerText'
			};
			if(options) {
				if(typeof options == "object") {
					for(var name in options) {
						var value = options[name];
						if(name == "styles") {
							this.setStyles(el, value);
						} else if(name == "html") {
							setInnerHTML(el, value);
						} else if (attributes[name]) {
							el[attributes[name]] = value;
						} else {
							el.setAttribute(name, value);
						}
					}
				}
			}
			return el;
		},

		addEvent: function(o, e, f) {
			if(o) {
				if(o.addEventListener) o.addEventListener(e, f, false);
				else if(o.attachEvent) o.attachEvent( 'on'+e , f);
			}
		},

		removeEvent: function(o, e, f) {
			if(o) {
				if(o.removeEventListener) o.removeEventListener(e, f, false);
				else if(o.detachEvent) o.detachEvent('on'+e, f);
			}
		},

		setStyles: function(e, o) {
			for(var k in o) {
				this.setStyle(e, k, o[k]);
			}
		},

		setStyle: function(e, p, v) {
			if (p == 'opacity') {
				this.topWindow.setOpacity(e,v);
			} else {
				e.style[p] = v;
			}
		},

		getPageSize: function() {
			return { 'pageWidth': this.topWindow.getWindowScrollWidth(this.topWindow), 'pageHeight': this.topWindow.getWindowScrollHeight(this.topWindow), 'windowWidth' : this.topWindow.getWindowWidth(), 'windowHeight': this.topWindow.getWindowHeight() };
		}
	};
}
{+END}