/*
 * YACOP: Yet Another Callout Plugin
 * @author: A.Lepe (www.alepe.com, www.support.ne.jp)
 * @since: 2010-05-19
 * @version: 2010-05-20
 * @link: http://yacop.alepe.com (For documentation, demo and gallery)
 *
 * CSS based in: http://www.dailycoding.com/Posts/purely_css_callouts.aspx
 * @param options: {
 * 		position: left|right|top|bottom
 * 		align:	center|left|right|top|bottom (where the callout should align in reference with the object)
 * 											  as expected you can not have "position: top" and "align: bottom" as they don't share axis.
 * 		pointer: none|left|right|top|bottom (where does the pointer is located. 'none' will hide it)
 * 		show: Initially shows the callout.
 * 		css: ".callout" css additional class to render the callout.
 * }
 *
 * HIDE callout:
 * 	$("div").callout("hide");
 *
 * SHOW hidden callout:
 * 	$("div").callout("show");
 *
 * DESTROY callout:
 * 	$("div").callout("destroy");
 *
 */
jQuery.fn.callout = function (options) {
		this.each(function() {
			var caller = this;
			if(options == "hide") {
				var cots = caller.callout;
				for(var c in cots) {
					cots[c].fadeOut(1000);
				}
			} else if(options == "show") {
				var cots = caller.callout;
				for(var c in cots) {
					cots[c].fadeIn(2000);
				}
				$(caller).callout("reorder");
			} else if(options == "destroy") {
				var cots = caller.callout;
				for(var c in cots) {
					cots[c].fadeOut(1000, function () {
						$(this).remove();
					});
				}
			} else if(options == "reorder") {
				var cots = caller.callout;
				var ltop	= ($.browser.safari)?$(caller).offset().top:$(caller).position().top;
				var lleft 	= ($.browser.safari)?$(caller).offset().left:$(caller).position().left;
				for(var c in cots) {
					if(cots[c].is(':visible')) {
						var ctop	= ($.browser.safari)?cots[c].offset().top:cots[c].position().top;
						var cleft 	= ($.browser.safari)?cots[c].offset().left:cots[c].position().left;
						var ltdiff = ltop - cots[c].attr("top");
						var lldiff = lleft - cots[c].attr("left");
						cots[c].css("top",  ctop + ltdiff);
						cots[c].css("left", cleft + lldiff);
						cots[c].attr("top", ltop);
						cots[c].attr("left",lleft);
					}
				}
			} else {
				options = $.extend({
					width 	: 'auto',
					height 	: 'auto',
					position: 'top',
					align	: 'center',
					pointer	: 'center',
					msg		: 'Example Text',
					css		: '',
					show	: 'true'			
				}, options || {});

				var position 	= options.position;
				var width 		= options.width;
				var height 		= options.height;
				var msg			= options.msg;
				var align 		= options.align;
				var pointer		= options.pointer;
				var css			= options.css;

				var $co = $("<div class='callout_main'></div>");
				var $cont = $("<div class='callout "+css+" callout_cont_"+position+"'>"+msg+"</div>");
				var $tri = $("<div class='callout_tri callout_"+position+"'></div>");
				var $tri2 = $("<div></div>");
				$tri.append($tri2);

				//Define default style
				$co.css("position","absolute").hide();
				$cont.css("zIndex",11);
				$tri.css("height",0).css("width",0).css("border","10px solid transparent").css("zIndex",10);
				$tri2.css("position","relative").css("border","10px solid transparent").css("height",0).css("width",0).css("zIndex",12);

				$co.append($cont);
				if(position == "bottom" || position == "right")
					$co.prepend($tri);
				else
					$co.append($tri);

				$("body").append($co);
				//Get callout style
				var importStyle = new Array(
						"backgroundColor",
						"borderTopColor","borderLeftColor","borderRightColor","borderBottomColor",
						"borderTopWidth","borderLeftWidth","borderRightWidth","borderBottomWidth",
						"marginTop","marginLeft","marginRight","marginBottom"
				);
				var s = {} //style object
				for(var i in importStyle) {
					s[importStyle[i]] = $cont.style(importStyle[i]);
				}

				$co.css("marginLeft",s.marginLeft).css("marginRight",s.marginRight).css("marginTop",s.marginTop).css("marginBottom",s.marginBottom);
				$cont.css("margin",0);
				// hide it fron the screen temporally to perform metrics
				var left = -1000;
				var top	 = -1000;
				$co.css("left", left);
				$co.css("top", top);
				$co.show();

				if(width != 'auto')  $co.css("width",width);
				if(height != 'auto') $cont.css("height",height);

				width 	 = $cont.width();
				height 	 = $cont.height();
				var ttop	= ($.browser.safari)?$(caller).offset().top:$(caller).position().top;
				var tleft 	= ($.browser.safari)?$(caller).offset().left:$(caller).position().left;
				var twidth  = $(caller).width();
				var theight = $(caller).height();
				$co.attr("left",tleft);
				$co.attr("top",ttop);

				// Restore non-sense settings
				if(position == "top" || position == "bottom") {
					if(align == "bottom" || align == "top") align = "center";
					if(pointer == "bottom" || pointer == "top") pointer = "center";
				} else {
					if(align == "left" || align == "right") align = "center";
					if(pointer == "left" || pointer == "right") pointer = "center";
				}
				switch(pointer) {
					case "none"	:  	$tri.hide(); break;
					case "left"	:  	$tri.css("marginLeft", 10); break;
					case "right":  	$tri.css("marginLeft", (width > 18) ? width - 10 - 8 : 0); break;
					case "top"	:	$tri.css("top", 10); break;
					case "bottom":	$tri.css("top", (height > 18) ? height - 10 - 8 : 0); break;
				}
				switch(align) {
					case "left"	 : left = tleft; break;
					case "right" : left = tleft + twidth - width - 8; break; //why 8?
					case "top"	 : top = ttop; break;
					case "bottom": top = ttop + theight - height - 10; break; //why 10?
				}
				switch(position) {
					case "top":
					case "bottom":
						if(position == "top") {
							top = ttop - height - 25 //25: just a margin (+ triangle height)
							$tri.css("marginTop",-1).css("borderTopColor",s.borderBottomColor);
							$tri2.css("borderTopColor",s.backgroundColor).css("left",-10).css("top",-12);
						} else {
							top = ttop + theight + 5; //5: just a margin
							$tri.css("marginBottom",-1).css("borderBottomColor",s.borderTopColor);
							$tri2.css("borderBottomColor",s.backgroundColor).css("left",-10).css("top",-8);
						}
						if(align == "center") left = tleft + (twidth / 2) - (width / 2);
						if(pointer == "center")
							$tri.css("marginLeft",(width / 2) - 8); //8: half of the triangle
						else if(pointer == "left" && align == "right") 
							left = tleft + (twidth) - 25; //25: slighly to the left
						else if(pointer == "right" && align == "left")
							left = tleft - width + 25; //25: slighly to the right
						
						if($.browser.opera) $tri2.hide(); //TODO: problem displaying tri2 in bottom and top
					break;
					case "left":
					case "right":
						if(position == "left") {
							left = tleft - width - 25; //25: triangle width + margin
							$tri.css("left",width + 10 + 1); //10: triangle width, 1: adjust border
							$tri.css("borderLeftColor",s.borderRightColor);
							$tri2.css("borderLeftColor",s.backgroundColor).css("left",-12).css("top",-10);
						} else {
							left = tleft + twidth + 15; //15: triangle width + margin
							$tri.css("left", - 19); //19: adjust margin
							$tri.css("borderRightColor",s.borderLeftColor);
							$tri2.css("borderRightColor",s.backgroundColor).css("left",-8).css("top",-10);
						}
						$tri.css("position","absolute");
						if(align == "center") top  = ttop + (theight / 2) - (height / 2) - 6; //6: adjust height
						if(pointer == "center") 
							$tri.css("top",(height / 2) - 4); //2: adjust single line
						else if(pointer == "top" && align == "bottom")
							top = ttop + theight - 30;  //25: slighly to the top
						else if(pointer == "bottom" && align == "top")
							top = ttop - height + 20; //25: slighly to the bottom
					break;
				}
				//Hide it and show it gracefuly
				$co.hide();
				$co.css("left",left);
				$co.css("top",top);
				if(options.show) $co.fadeIn(2000);
				if(caller.callout == undefined) {
					caller.callout = new Array();
					$(window).bind("resize", function resizeWindow( e ) {
						$(caller).callout("reorder");
					});
				}
				caller.callout.push($co);
			}
		});
	return this;
}
// From the net (unknown author) and converted by A.Lepe
jQuery.fn.style = function (property){ var el = this[0]; if (el.currentStyle) return el.currentStyle[property]; else if (document.defaultView && document.defaultView.getComputedStyle) return document.defaultView.getComputedStyle(el, "")[property]; else return el.style[property]; }