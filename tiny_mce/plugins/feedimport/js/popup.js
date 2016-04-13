var feedimportDialog = {
	init : function(ed) {
		var dom = ed.dom,
		f = document.forms[0],
		n = ed.selection.getNode(),
		w = dom.getAttrib(n, 'feedid');
		
		

		//f.feedid.value = w ? parseInt(w) : (dom.getStyle('feedid') || '');
		f.numberoffeeds.value = dom.getAttrib(n, 'numberoffeeds') || parseInt(dom.getStyle('numberoffeeds')) || '';
		f.numberofletters.value = dom.getAttrib(n, 'numberofletters') || parseInt(dom.getStyle('numberofletters')) || '';
	},

	update : function() {
		var ed = tinyMCEPopup.editor,
		//mainpart,
		f = document.forms[0],
		addAlt = ' alt="',
		addTitle = ' title="',
		//feed = f.feedid.value,
		feedNumNamePattern = /([0-9]+)\:\s+([^\s]*)/,
		feedNumName = feedNumNamePattern.exec(f.feedid[f.feedid.selectedIndex].text),
		feed = feedNumName[1],
		feedName = feedNumName[2],
		
		maxnum = f.numberoffeeds.value,
		maxlett = f.numberofletters.value,
		url = tinyMCEPopup.getWindowArg('plugin_url'),
		//mainpart = '<!--#include virtual="/cgi-bin/feeds/feedimport.pl';
		mainpart = '<img src="' + url + '/img/feedimport.jpg"';
		if(feed != ''){
			addAlt += '/'+feed;
			addTitle += 'Feed-'+ feed + ' ' + feedName;
			if(maxnum != ''){
				addAlt += '/'+maxnum;
				addTitle += ' | Max Artikel: ' + maxnum;
				if(maxlett != ''){
					addAlt += '/'+maxlett;
					addTitle += ' | Max Zeichen: ' + maxlett;
				}
			}
		}
		//mainpart += add + '" -->';
		mainpart += addAlt + '"' + addTitle + '" class="addFeed" />';
		if(feed == ''){
			alert("Bitte geben Sie Feed Id ein!");
			f.feedid.focus();
		}else{
			ed.execCommand("mceInsertContent", false, mainpart);
			tinyMCEPopup.close();
		}
	}
};

//tinyMCEPopup.requireLangPack();
tinyMCEPopup.onInit.add(feedimportDialog.init, feedimportDialog);