/**
 * editor_plugin_src.js
 *
 * Copyright 2009, Moxiecode Systems AB
 * Released under LGPL License.
 *
 * License: http://tinymce.moxiecode.com/license
 * Contributing: http://tinymce.moxiecode.com/contributing
 */

(function() {
	// Load plugin specific language pack
	// tinymce.PluginManager.requireLangPack('mitarbeiter');
	
	tinymce.create('tinymce.plugins.MitarbeiterPlugin', {

		init : function(ed, url) {
			var ma_img = '<img title="Mitarbeiter" src="' + url +'/img/mitarbeiter.jpg" alt="" />',
			cls = 'addMitarbeiter',
			ma_inhalt = '<!--#include virtual="/cgi-bin/univis/mitarbeiter.pl" -->';
			// Register the command
			ed.addCommand(cls, function() {
				ed.execCommand('mceInsertContent', 0, ma_img);
			});

			// Register example button
			ed.addButton('mitarbeiter', {
				title : 'Mitarbeiter Liste Einfuegen',
				cmd : cls,
				image : url + '/img/ma_ico.gif'
			});
			
			ed.onClick.add(function(ed, e) {
				e = e.target;

				if (e.nodeName === 'IMG' && ed.dom.hasClass(e, cls))
					ed.selection.select(e);
			});

			// Add a node change handler, selects the button in the UI when a image is selected
			ed.onNodeChange.add(function(ed, cm, n) {
				cm.setActive('mitarbeiter', n.nodeName == 'IMG' && ed.dom.hasClass(n, cls));
			});
			
			ed.onBeforeSetContent.add(function(ed, o) {
				o.content = o.content.replace(/<!--#include virtual=\"\/cgi-bin\/univis\/mitarbeiter\.pl\" -->/g, ma_img);
			});
			
			ed.onPostProcess.add(function(ed, o) {
				if (o.get){
					while(o.content.search(ma_img) != -1){
						o.content = o.content.replace(ma_img, ma_inhalt);
					}
				}
			});
		},

		getInfo : function() {
			return {
				longname : 'Mitarbeiter plugin',
				author : 'Dmitry',
				authorurl : '',
				infourl : '',
				version : "1.0"
			};
		}
	});

	// Register plugin
	tinymce.PluginManager.add('mitarbeiter', tinymce.plugins.MitarbeiterPlugin);
})();