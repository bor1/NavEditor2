/**
 * editor_plugin_src.js
 *
 * Copyright 2009, Moxiecode Systems AB
 * Released under LGPL License.
 *
 * License: http://tinymce.moxiecode.com/license
 * Contributing: http://tinymce.moxiecode.com/contributing
 */
var feedData;
$.ajax({
	url: "app/edit_conf.php?r=" + Math.random() + "&oper=get_feedimport&conf_file_name=feedimport.conf",
	async: false,
	dataType: 'json',
	success: function(data) {
			feedData = data;
	}
});
(function() {
	// Load plugin specific language pack
	// tinymce.PluginManager.requireLangPack('feedimport');
	
	tinymce.create('tinymce.plugins.FeedImportPlugin', {

		init : function(ed, url) {
			var feedtmp='',
			cls = 'addFeed',
			//feedData = ed.getParam('feed_number'),
			feed_begin = '<!--#include virtual="/cgi-bin/feeds/feedimport.pl';
			// Register the command
			function feed_img(altF, titleF) {return '<img src="' + url + '/img/feedimport.jpg" alt="' + altF + '" title="' + titleF + '" class="addFeed" />';}
			ed.addCommand('addFeed', function() {
				ed.windowManager.open({
					file : url + '/popup.htm',
					width : 400 + parseInt(ed.getLang('feedimport.delta_width', 0)),
					height : 180 + parseInt(ed.getLang('feedimport.delta_height', 0)),
					inline : 1
				}, {
					plugin_url : url,
					feedsnum : feedData
				});
			});

			// Register example button
			ed.addButton('feedimport', {
				title : 'Feed einfuegen',
				cmd : cls,
				image : url + '/img/feed_ico.gif'
			});
			
			ed.onClick.add(function(ed, e) {
				e = e.target;

				if (e.nodeName === 'IMG' && ed.dom.hasClass(e, cls))
					ed.selection.select(e);
			});

			// Add a node change handler, selects the button in the UI when a image is selected
			ed.onNodeChange.add(function(ed, cm, n) {
				cm.setActive('feedimport', n.nodeName == 'IMG' && ed.dom.hasClass(n, cls));
			});
			
			ed.onBeforeSetContent.add(function(ed, o) {
				var include_pattern = /<!--#include virtual=\"\/cgi-bin\/feeds\/feedimport\.pl(\/?([0-9]*)\/?([0-9]*)\/?([0-9]*))\" -->/;
				var feedtmparray;
				while(o.content.search(include_pattern) != -1){
					feedtmparray = include_pattern.exec(o.content);
					alt = feedtmparray[1];
					addTitle = "";
					if(feedtmparray[2]){
						addTitle += 'Feed-' + feedtmparray[2] + ' ' + feedData.feeds[(feedtmparray[2]-1)].title;
						if(feedtmparray[3]){
							addTitle += ' | Max Artikel: ' + feedtmparray[3];
							if(feedtmparray[4]){
								addTitle += ' | Max Zeichen: ' + feedtmparray[4];
							}
						}
					}
					// if (feedtmparray[2])
					// title1 = feedtmparray[2];
					// if (feedtmparray[3])
					// title2 = feedtmparray[1];
					// if (feedtmparray[4])
					// title3 = feedtmparray[1];
					// //tittle = "";
					// title = title1+title2+title3;
					o.content = o.content.replace(include_pattern, feed_img(alt, addTitle));
				}
			
				//o.content = o.content.replace(/<!--#include virtual=\"\/cgi-bin\/feeds\/feedimport\.pl\/?[0-9]*\/?[0-9]*\/?[0-9]*\" -->/g, feed_img());
			});
			
			ed.onPostProcess.add(function(ed, o) {
				if (o.get){
					var img_pattern = /<img class=\"addFeed\"[^>]+alt=\"(\/?[0-9]*\/?[0-9]*\/?[0-9]*)[^>]+>/;
					var feedtmparray;
					while(o.content.search(img_pattern) != -1){
						feedtmparray = img_pattern.exec(o.content);
						feedtmp = feedtmparray[1];
						o.content = o.content.replace(img_pattern, function(im) {
						//if (im.indexOf('class="addFeed"') !== -1)
							im = feed_begin+feedtmp+'" -->';
						return im;
						});
					}
				}
			});
			// ed.onSetContent.add(function(ed, o) {
           		// o.content = o.content.replace(/<!--#include virtual=\"\/cgi-bin\/feeds\/feedimport\.pl\/?[0-9]*\/?[0-9]*\/?[0-9]*\" -->/g, feed_img());
			// });

		},

		getInfo : function() {
			return {
				longname : 'Feedimport plugin',
				author : 'Dmitry',
				authorurl : '',
				infourl : '',
				version : "1.0"
			};
		}
	});

	// Register plugin
	tinymce.PluginManager.add('feedimport', tinymce.plugins.FeedImportPlugin);
})();