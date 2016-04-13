// jQuery File Tree Plugin
//
// Version 1.01
//
// Cory S.N. LaViska
// A Beautiful Site (http://abeautifulsite.net/)
// 24 March 2008
//
// Visit http://abeautifulsite.net/notebook.php?article=58 for more information
//
// Usage: $('.fileTreeDemo').fileTree( options, callback )
//
// Options:  root           - root folder to display; default = /
//           script         - location of the serverside AJAX file to use; default = jqueryFileTree.php
//           folderEvent    - event to trigger expand/collapse; default = click
//           expandSpeed    - default = 500 (ms); use -1 for no animation
//           collapseSpeed  - default = 500 (ms); use -1 for no animation
//           expandEasing   - easing function to use on expand (optional)
//           collapseEasing - easing function to use on collapse (optional)
//           multiFolder    - whether or not to limit the browser to one subfolder at a time
//           loadMessage    - Message to display while initial tree loads (can be HTML)
//
// History:
//
// 1.01 - updated to work with foreign characters in directory/file names (12 April 2008)
// 1.00 - released (24 March 2008)
//
// TERMS OF USE
// 
// This plugin is dual-licensed under the GNU General Public License and the MIT License and
// is copyright 2008 A Beautiful Site, LLC. 
//
var fileInfoArray = {};
if(jQuery) (function($){
	
	$.extend($.fn, {
		fileTree: function(o, h) {
			// Defaults
			if( !o ) var o = {};
			if( o.root == undefined ) o.root = '/';
			if( o.script == undefined ) o.script = 'app/jqueryFileTree.php';
			if( o.folderEvent == undefined ) o.folderEvent = 'click';
			if( o.expandSpeed == undefined ) o.expandSpeed= 500;
			if( o.collapseSpeed == undefined ) o.collapseSpeed= 500;
			if( o.expandEasing == undefined ) o.expandEasing = null;
			if( o.collapseEasing == undefined ) o.collapseEasing = null;
			if( o.multiFolder == undefined ) o.multiFolder = true;
			if( o.loadMessage == undefined ) o.loadMessage = 'Loading...';
                        if( o.rechte == undefined ) o.rechte = '0';
                        if( o.expandCallBack == undefined ) o.expandCallBack = null;
                        if( o.collapseCallBack == undefined ) o.collapseCallBack = null;
                        if( o.loadCallBack == undefined ) o.loadCallBack = function(){};
                        if( o.checkPermFunc == undefined ) o.checkPermFunc = function(){return true;};
			
			$(this).each( function() {
				
				function showTree(c, t) {
					$(c).addClass('wait');
					$(".jqueryFileTree.start").remove();
					$.post(o.script, { dir: t, rechte: o.rechte }, function(data) {
						$(c).find('.start').html('');
						$(c).removeClass('wait').append(JSON.parse(data).html);
						//add to object
						for(var property in JSON.parse(data).filesinfo){
                                                    var dataTmp = JSON.parse(data);
							fileInfoArray[dataTmp.filesinfo[property].url] = dataTmp.filesinfo[property];
						}
						
						//if( o.root == t ) $(c).find('UL:hidden').show(); else $(c).find('UL:hidden').slideDown({ duration: o.expandSpeed, easing: o.expandEasing, callback: o.expandCallBack });
                                                if( o.root == t ) $(c).find('UL:hidden').show(); else $(c).find('UL:hidden').slideDown(o.expandSpeed, o.expandEasing, o.expandCallBack);
						bindTree(c);
                                                o.loadCallBack();
					});
				}
				
				function bindTree(t) {
					$(t).find('LI A').bind(o.folderEvent, function() {
                                                //if permission check function from current relation = false 
                                                if(!o.checkPermFunc($(this).attr('rel'))){
                                                    //do nothing
                                                    return false;
                                                }
						if( $(this).parent().hasClass('directory') ) {
							if( $(this).parent().hasClass('collapsed') ) {
								// Expand
								if( !o.multiFolder ) {
									$(this).parent().parent().find('UL').slideUp(o.collapseSpeed, o.collapseEasing, o.collapseCallBack);
									$(this).parent().parent().find('LI.directory').removeClass('expanded').addClass('collapsed');
								}
								$(this).parent().find('UL').remove(); // cleanup
								showTree( $(this).parent(), escape($(this).attr('rel').match( /.*\// )) );
								$(this).parent().removeClass('collapsed').addClass('expanded');
							} else {
								// Collapse
								$(this).parent().find('UL').slideUp(o.collapseSpeed,o.collapseEasing, o.collapseCallBack);
								$(this).parent().removeClass('expanded').addClass('collapsed');
							}
							h(null, $(this).attr('rel'));
						} else {
							h($(this).attr('rel'), null);
						}
						return false;
					});
					// Prevent A from triggering the # on non-click events
					if( o.folderEvent.toLowerCase != 'click' ) $(t).find('LI A').bind('click', function() { return false; });
				}
				// Loading message
				$(this).html('<ul class="jqueryFileTree start"><li class="wait">' + o.loadMessage + '<li></ul>');
				// Get the initial file list
				showTree( $(this), escape(o.root) );
			});
		}
	});
	
})(jQuery);