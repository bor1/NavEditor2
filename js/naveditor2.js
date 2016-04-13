/**
 * @author ke.chang@rrze.uni-erlangen.de
 */

// navTree operating object. requires jQuery!

var navTreeOper = {
	_cloneObject: function(obj) {
		if(typeof obj !== 'object' || obj == null)
			return obj;
		
		var c = obj instanceof Array ? [] : {};
		for(var i in obj) {
			var prop = obj[i];
			if(typeof prop == 'object') {
				if(prop instanceof Array) {
					c[i] = [];
					for(var j = 0; j < prop.length; j++) {
						if(typeof prop[j] != 'object') {
							c[i].push(prop[j]);
						} else {
							c[i].push(this._cloneObject(prop[j]));
						}
					}
				} else {
					c[i] = this._cloneObject(prop);
				}
			} else {
				c[i] = prop;
			}
		}
		return c;
	},
	
	_jsonObject: {},
	_jsonString: "",
	
	// 3 parts json: A, Z, S
	_jsonNavNormal: [],
	_jsonNavExtra: [],
	_jsonNavSpec: [],
	
	_dirHtmlNormal: "",
	_dirHtmlExtra: "",
	_dirHtmlSpec: "",
	
	// current objects
	_currentKey: "",
	_currentNode: {},
	_currentNodeGroup: [],
	
	// oper flags
	_canAddSibling: false,
	_canAddChild: false,
//	_canCut: false,
	_canRemove: false,
//	_canPasteAsSibling: false,
//	_canPasteAsChild: false,
	_canMoveUp: false,
	_canMoveDown: false,
	
	_clipBoard: {
		depth: 0,
		data: {}
	},
	
	_treeDepth: 1,
	
	_undoStack: [],
	
	// html containers
	dirHtmlPanelId: "",
	dirDivIdNormal: "",
	dirDivIdExtra: "",
	dirDivIdSpec: "",
	debugInfoPanelId: "",
	
	nodeEventFuncName: "",
	withInterfaceElements: true,
	
	setCurrentKey: function(ckey) {
		this._currentKey = ckey;
	},
	
	setCurrentNode: function(cnode) {
		this._currentNode = cnode;
		this._currentKey = cnode.key;
	},
	
	setCurrentNodeGroup: function(groupType) {
		switch(groupType) {
			case "Z":
				this._currentNodeGroup = this._jsonNavExtra;
				break;
			case "S":
				this._currentNodeGroup = this._jsonNavSpec;
				break;
			default:
				alert("setCurrentNodeGroup error!");
				break;
		}
	},
	
	getCurrentNode: function() {
		return this._currentNode;
	},
	
	getCurrentNodeGroup: function() {
		return this._currentNodeGroup;
	},
	
	_hasClipData: function() {
		if((this._clipBoard.depth > 0) && (this._clipBoard.data != null)) {
			return true;
		} else {
			return false;
		}
	},
	
	_getNodeType: function(nodeKey) {
		var firstLetter = nodeKey.substr(0, 1);
		if((firstLetter.toUpperCase() == "S") || (firstLetter.toUpperCase() == "Z")) {
			return firstLetter;
		} else {
			return "A";
		}
	},
	
	_genDirHtmlNormal: function(jsonData) {
		if(jsonData.length < 1) {
			return;
		}
		this._dirHtmlNormal += "<ul>";
		for(var i = 0; i < jsonData.length; i++) {
			var titleText = '';
			if (jsonData[i].title_icon != ''){
				titleText = "<img alt=\"" + jsonData[i].title_icon_alt + "\" src=\"" + jsonData[i].title_icon + "\" />";
			}
			titleText += jsonData[i].title;
			this._dirHtmlNormal += "<li rel=\"" + this.getPathInfo(jsonData[i]) + "\">" + (jsonData[i].child.length > 0 ? "<b class=\"tg\">-</b> " : "&nbsp; ") + "<em class=\"treeKey\">" + jsonData[i].key + "</em><span id=\"" + jsonData[i].key + "\">" + titleText + "</span>";
			if(jsonData[i].child.length > 0) {
				this._genDirHtmlNormal(jsonData[i].child); // recursive
			}
			this._dirHtmlNormal += "</li>";
		}
		this._dirHtmlNormal += "</ul>";
	},
	
	_genDirHtmlExtra: function(jsonData) {
		if(jsonData.length < 1) {
			return;
		}
		this._dirHtmlExtra += "<ul>";
		for(var j = 0; j < jsonData.length; j++) {
			var titleText = '';
			if (jsonData[j].title_icon != ''){
				titleText = "<img alt=\"" + jsonData[j].title_icon_alt + "\" src=\"" + jsonData[j].title_icon + "\" />";
			}
			titleText += jsonData[j].title;
			this._dirHtmlExtra += "<li rel=\"" + this.getPathInfo(jsonData[j]) + "\"><span id=\"" + jsonData[j].key + "\">" + titleText + "</span></li>";
		}
		this._dirHtmlExtra += "</ul>";
	},
	
	_genDirHtmlSpec: function(jsonData) {
		if(jsonData.length < 1) {
			return;
		}
		this._dirHtmlSpec += "<ul>";
		for(var k = 0; k < jsonData.length; k++) {
			var titleText = '';
			if (jsonData[k].title_icon != ''){
				titleText = "<img alt=\"" + jsonData[k].title_icon_alt + "\" src=\"" + jsonData[k].title_icon + "\" />";
			}
			titleText += jsonData[k].title;
			this._dirHtmlSpec += "<li rel=\"" + this.getPathInfo(jsonData[k]) + "\"><span id=\"" + jsonData[k].key + "\">" + titleText + "</span></li>";
		}
		this._dirHtmlSpec += "</ul>";
	},
	
	init: function() {
		$("#" + this.dirDivIdNormal).empty();
		
		this._dirHtmlNormal = "";
		this._dirHtmlExtra = "";
		this._dirHtmlSpec = "";
		
		this._currentKey = "";
		this._currentNode = {};
		this._currentNodeGroup = [];
		
		this._canAddSibling = false;
		this._canAddChild = false;
//		this._canCut = false;
//		this._canPasteAsSibling = false;
//		this._canPasteAsChild = false;
		this._canRemove = false;
		this._canMoveUp = false;
		this._canMoveDown = false;
		
		$("#" + this.debugInfoPanelId).text("Ready.");
		
		this.updateButtons();
	},
	
	locateNode: function(nodeKey) {
		var ntyp = this._getNodeType(nodeKey);
		if(ntyp != "A") { // S and Z pages, are all on root (no hierarchy)
			if(ntyp == "Z")
				this._currentNodeGroup = this._jsonNavExtra;
			else
				this._currentNodeGroup = this._jsonNavSpec;
			
			for(var j = 0; j < this._currentNodeGroup.length; j++) {
				if(this._currentNodeGroup[j].key == nodeKey) {
					this._canAddSibling = true;
					this._canAddChild = false;
/*					this._canCut = false;
					this._canPasteAsSibling = false;
					this._canPasteAsChild = false;*/
					this._canRemove = true;
					this._canMoveUp = (j == 0) ? false : true;
					this._canMoveDown = (j == this._currentNodeGroup.length - 1) ? false : true;
					
					return this._currentNodeGroup[j];
				}
			}
		} else { // normal pages, but be ware of U
			if(nodeKey == "U") { // special
				this._currentNodeGroup = this._jsonNavNormal;
				
				this._canAddSibling = true;
				this._canAddChild = false;
/*				this._canCut = false;
				if(this._hasClipData()) {
					this._canPasteAsSibling = true;
				}
				this._canPasteAsChild = false;*/
				this._canRemove = false;
				this._canMoveUp = false;
				this._canMoveDown = false;
				
				return this._jsonNavNormal[0];
			} else { // normal node, with hierarchy
				var arrKeys = nodeKey.split("-");
				if(arrKeys.length == 1) { // root level: Ax
					this._currentNodeGroup = this._jsonNavNormal;
					
					this._canAddSibling = true;
					this._canAddChild = true;
/*					this._canCut = true;
					if(this._hasClipData()) {
						this._canPasteAsSibling = true;
						this._canPasteAsChild = true;
					}*/
					this._canRemove = true;
					
					var arrRootLevelKeys = /A(\d+)/.exec(nodeKey);
					var rootIdx = arrRootLevelKeys[1] * 1; // convert to int
					
					this._canMoveUp = (rootIdx == 1) ? false : true;
					this._canMoveDown = (rootIdx == this._currentNodeGroup.length - 1) ? false : true;
					
					return this._jsonNavNormal[rootIdx];
				} else { // most complicated part
					// >_<...
					this._canAddSibling = true;
					this._canAddChild = true;
/*					this._canCut = true;
					if(this._hasClipData()) {
						this._canPasteAsSibling = true;
						this._canPasteAsChild = true;
					}*/
					this._canRemove = true;
					
					var idx0 = arrKeys[0].substr(1) * 1;
					var idx1 = arrKeys[arrKeys.length - 1].substr(1) * 1;
					this._currentNodeGroup = this._jsonNavNormal[idx0].child;
					for(var i = 2; i < arrKeys.length; i++) { // start from 3rd...
						idx0 = arrKeys[i - 1].substr(1) * 1;
						this._currentNodeGroup = this._currentNodeGroup[idx0 - 1].child; // root level, number = index, deeper ones must -1
					}
					
					this._canMoveUp = (idx1 - 1 == 0) ? false : true;
					this._canMoveDown = (idx1 == this._currentNodeGroup.length) ? false : true;
					
					return this._currentNodeGroup[idx1 - 1];
				}
			}
		}
	},
	
	updateButtons: function() {
		$("#btnAddSibling").attr("disabled", !this._canAddSibling);
		$("#btnAddChild").attr("disabled", !this._canAddChild);
//		$("#btnCut").attr("disabled", !this._canCut);
//		$("#btnPaste0").attr("disabled", !this._canPasteAsSibling);
//		$("#btnPaste1").attr("disabled", !this._canPasteAsChild);
		$("#btnRemove").attr("disabled", !this._canRemove);
		$("#btnMoveUp").attr("disabled", !this._canMoveUp);
		$("#btnMoveDown").attr("disabled", !this._canMoveDown);
	},
	
	refreshNavTree: function(jsonTreeData) {
		if(this.withInterfaceElements) {
			this.init();
			$("#" + this.debugInfoPanelId).text("Tree-Data loaded...");
		}
		
		this._jsonObject = jsonTreeData;
		this._jsonNavNormal = jsonTreeData.A;
		this._jsonNavExtra = jsonTreeData.Z;
		this._jsonNavSpec = jsonTreeData.S;
		
		this._reIndex(this._jsonNavNormal);
		this._reIndex(this._jsonNavExtra);
		this._reIndex(this._jsonNavSpec);
		
		this.addPathInfo(this._jsonNavNormal);
		this.addPathInfo(this._jsonNavExtra);
		this.addPathInfo(this._jsonNavSpec);
		
		if(this.withInterfaceElements) {
			this._genDirHtmlNormal(this._jsonNavNormal);
			this._genDirHtmlExtra(this._jsonNavExtra);
			this._genDirHtmlSpec(this._jsonNavSpec);
			
			$("#" + this.dirDivIdNormal).html(this._dirHtmlNormal);
			$("#" + this.dirDivIdExtra).html(this._dirHtmlExtra);
			if(this._dirHtmlExtra == "") {
				$("#btnNewExtraNode").show();
			} else {
				$("#btnNewExtraNode").hide();
			}
			$("#" + this.dirDivIdSpec).html(this._dirHtmlSpec);
			if(this._dirHtmlSpec == "") {
				$("#btnNewSpecNode").show();
			} else {
				$("#btnNewSpecNode").hide();
			}
			
			eval(this.nodeEventFuncName);
		}
	},
	
	_hasDupTitle: function(title, nodes) {
		for(var i = 0, li = nodes.length; i < li; i++) {
			if(nodes[i].title.toLowerCase() == title.toLowerCase()) {
				alert("Error: There are duplicate titles, please retry!");
				return true;
			}
		}
		return false;
	},
	
	_checkDupFileName: function(title, nodes) {
		var fname = this.getFileName2(title);
		
		for(var i = 0, li = nodes.length; i < li; i++) {
			if(this.getFileName(nodes[i]) == fname) {
				var alias = prompt("There is already a file with the same name, you must specify an alias:");
				return alias;
			}
		}
		return false;
	},
	
	_reIndex: function(nodes) {
		if(nodes.length < 1) {
			return;
		}
		var _pad = false; // add forwarding 0s to keys, currently max. only 2 digis
		if(nodes.length > 9) {
			_pad = true;
		}
		
		var testKey = nodes[0].key;
		var typ = this._getNodeType(testKey);
		if(typ == "A") { // normal nodes
			// reindex normal nodes, recursive
			if(testKey.substr(0, 1) == "U") { // root level
				for(var i = 1; i < nodes.length; i++) {
					nodes[i].key = "A" + i.toString();
					if(_pad) {
						if(i < 10) {
							nodes[i].key = "A0" + i.toString();
						}
					}
					if(nodes[i].child.length > 0) {
						var oldChildKey0 = nodes[i].child[0].key;
						nodes[i].child[0].key = oldChildKey0.replace(new RegExp("A(\\d+)"), "A" + i.toString());
						if(_pad) {
							if(i < 10) {
								nodes[i].child[0].key = oldChildKey0.replace(new RegExp("A(\\d+)"), "A0" + i.toString());
							}
						}
						this._reIndex(nodes[i].child);
					}
				}
			} else { // Ax-Bx-...
				// ...
				var arrKeys = testKey.split("-");
				var lastKeyComp = String.fromCharCode(64 + arrKeys.length);
				var fixedPart = testKey.substring(0, testKey.lastIndexOf("-")) + "-";
				for(var k = 0; k < nodes.length; k++) {
					nodes[k].key = fixedPart + lastKeyComp + (k + 1).toString();
					if(_pad) {
						if((k + 1) < 10) {
							nodes[k].key = fixedPart + lastKeyComp + "0" + (k + 1).toString();
						}
					}
					if(nodes[k].child.length > 0) {
						var oldChildKey1 = nodes[k].child[0].key;
						nodes[k].child[0].key = oldChildKey1.replace(oldChildKey1.substring(0, oldChildKey1.lastIndexOf("-")), nodes[k].key);
						this._reIndex(nodes[k].child);
					}
				}
			}
		} else { // extra nodes or spec nodes
			for(var j = 0; j < nodes.length; j++) {
				nodes[j].key = typ + (j + 1).toString();
			}
		}
	},
	
	addSibling: function(titleText) {
		if(this._jsonObject == null) {
			alert("ERROR: _jsonObject == null");
			return null;
		}
		
		if(this._hasDupTitle(titleText, this._currentNodeGroup)) {
			return null;
		}
		
		var alias = this._checkDupFileName(titleText, this._currentNodeGroup);
		var alias2 = alias;
		while(alias != false) {
			alias2 = alias;
			alias = this._checkDupFileName(alias, this._currentNodeGroup);
		}
		
		var newNodeObj = {
			"key": "",
			"title": titleText,
			"child": [],
			"alias": (alias2 == false) ? "" : alias2,
			"accesskey": "",
			"email": "",
			"path": "",
			"quicklink": false,
			"displaylink": false,
			"url": "",
			"info_text": "",
			"title_icon": "",
			"title_icon_alt": "",
			"title_icon_title": "",
			"title_display": ""
		};
		
		var path = this._currentKey;
		var arrPath = path.split("-");
		switch(arrPath.length) {
			case 1:
				var idx = 0;
				switch(path.substr(0, 1)) {
					case "U":
						newNodeObj.key = "A1";
						idx = 1;
						break;
					case "Z":
						var keysZ = /Z(\d+)/.exec(path);
						var zx = keysZ[1] * 1;
						newNodeObj.key = "Z" + (zx + 1).toString();
						idx = zx;
						break;
					case "S":
						var keysS = /S(\d+)/.exec(path);
						var sx = keysS[1] * 1;
						newNodeObj.key = "S" + (sx + 1).toString();
						idx = sx;
						break;
					case "A":
						var keysA = /A(\d+)/.exec(path);
						var ax = keysA[1] * 1;
						newNodeObj.key = "A" + (ax + 1).toString();
						idx = ax + 1;
						break;
					default:
						alert("Errr...");
						break;
				}
				this._currentNodeGroup.splice(idx, 0, newNodeObj);
				this._reIndex(this._currentNodeGroup);
				break;
			default:
				var arrlastPart = /([A-Z])(\d+)/.exec(arrPath[arrPath.length - 1]);
				var idx1 = arrlastPart[2] * 1;
				this._currentNodeGroup.splice(idx1, 0, newNodeObj);
				this._reIndex(this._currentNodeGroup);
				break;
		}
		this.refreshNavTree(this._jsonObject);
		return newNodeObj;
	},
	
	addChild: function(titleText) {
		if(this._jsonObject == null) {
			alert("ERROR: _jsonObject == null");
			return null;
		}
		
		if(this._hasDupTitle(titleText, this._currentNode.child)) {
			return null;
		}
		
		var newChildObj = {
			"key": "",
			"title": titleText,
			"child": [],
			"alias": "",
			"accesskey": "",
			"email": "",
			"path": "",
			"quicklink": false,
			"displaylink": false,
			"url": "",
			"info_text": "",
			"title_icon": "",
			"title_icon_alt": "",
			"title_icon_title": "",
			"title_display": ""
		};
		
		var path = this._currentKey;
		var arrPath = path.split("-");
		switch(arrPath.length) {
			case 1:
				switch(path.substr(0, 1)) {
					case "A":
						if(this._currentNode.child.length < 1) {
							var arrRootKeys = /A(\d+)/.exec(path);
							var ax = arrRootKeys[0] + "";
							newChildObj.key = ax + "-B1";
						}
//						this._currentNode.child.unshift(newChildObj); // new child should be at place 0
						this._currentNode.child.push(newChildObj); // new child should be at the last place
						this._reIndex(this._currentNode.child);
						break;
				}
				break;
			default:
				var arrLastLetter = /([A-Z])/.exec(arrPath[arrPath.length - 1]);
				var lastLetter = arrLastLetter[1];
				if(lastLetter == "Z") {
					alert("No more Child-Node allowed!");
					return;
				}
				if(this._currentNode.child.length < 1) {
					var newLetter = String.fromCharCode(lastLetter.charCodeAt(0) + 1);
					newChildObj.key = path + "-" + newLetter + "1";
				}
//				this._currentNode.child.unshift(newChildObj);
				this._currentNode.child.push(newChildObj);
				this._reIndex(this._currentNode.child);
				break;
		}
		this.refreshNavTree(this._jsonObject);
		return newChildObj;
	},
	
	remove: function() {
		if(this._jsonObject == null) {
			alert("ERROR: _jsonObject == null");
			return null;
		}
		for(var i = 0; i < this._currentNodeGroup.length; i++) {
			if(this._currentNodeGroup[i].key == this._currentKey) {
				this._currentNodeGroup.splice(i, 1);
				break;
			}
		}
//		this._reIndex(this._currentNodeGroup);
		this.refreshNavTree(this._jsonObject);
	},
	
	_getTreeDepth: function(tree) {
		if(tree == null) {
			return;
		}
		if(tree.length > 0) {
			this._treeDepth++;
		}
		for(var t = 0; t < tree.length; t++) {
			if(tree[t].child.length > 0) {
				this._getTreeDepth(tree[t].child);
			}
		}
	},
	
	cut: function() {
		// tbd...
		this._treeDepth = 1;
		this._getTreeDepth(this._currentNode.child);
		
		this._clipBoard.depth = this._treeDepth;
		this._clipBoard.data = this._currentNode;
		
		for(var i = 0; i < this._currentNodeGroup.length; i++) {
			if(this._currentNodeGroup[i].key == this._currentKey) {
				this._currentNodeGroup.splice(i, 1);
				break;
			}
		}
	},
	
	copy: function() {
		this._treeDepth = 1;
		this._getTreeDepth(this._currentNode.child);
		
		this._clipBoard.depth = this._treeDepth;
		this._clipBoard.data = this._currentNode;
		
/*		for(var i = 0; i < this._currentNodeGroup.length; i++) {
			if(this._currentNodeGroup[i].key == this._currentKey) {
				this._currentNodeGroup.splice(i, 1);
				break;
			}
		}*/
	},
	
	pasteAsSibling: function() {
		// almost the same as addSibling()
		if(this._jsonObject == null) {
			alert("ERROR: _jsonObject == null");
			return;
		}
		
		var newNodeObj = this._cloneObject(this._clipBoard.data);
		
		if(this._hasDupTitle(newNodeObj.title, this._currentNodeGroup)) {
			return;
		}
		
/*		var titleText = newNodeObj.title;
		var alias = this._checkDupFileName(titleText, this._currentNodeGroup);
		var alias2 = alias;
		while(alias != false) {
			alias2 = alias;
			alias = this._checkDupFileName(alias, this._currentNodeGroup);
		}*/
		
		var path = this._currentKey;
		var arrPath = path.split("-");
		switch(arrPath.length) {
			case 1:
				var idx = 0;
				switch(path.substr(0, 1)) {
					case "U":
						newNodeObj.key = "A1";
						idx = 1;
						break;
					case "Z":
						var keysZ = /Z(\d+)/.exec(path);
						var zx = keysZ[1] * 1;
						newNodeObj.key = "Z" + (zx + 1).toString();
						idx = zx;
						break;
					case "S":
						var keysS = /S(\d+)/.exec(path);
						var sx = keysS[1] * 1;
						newNodeObj.key = "S" + (sx + 1).toString();
						idx = sx;
						break;
					case "A":
						var keysA = /A(\d+)/.exec(path);
						var ax = keysA[1] * 1;
						newNodeObj.key = "A" + (ax + 1).toString();
						idx = ax + 1;
						break;
					default:
						alert("Errr...");
						break;
				}
//				this._currentNodeGroup.splice(idx, 0, newNodeObj);
				this._currentNodeGroup.push(newNodeObj);
//				this._reIndex(this._currentNodeGroup);
				break;
			default:
				if((path.length + this._clipBoard.depth - 1) > 26) {
					// level too deep
					alert("Level too deep!");
					return;
				}
				
				var arrlastPart = /([A-Z])(\d+)/.exec(arrPath[arrPath.length - 1]);
				var idx1 = arrlastPart[2] * 1;
//				this._currentNodeGroup.splice(idx1, 0, newNodeObj);
				this._currentNodeGroup.push(newNodeObj);
//				this._reIndex(this._currentNodeGroup);
				break;
		}
		this.refreshNavTree(this._jsonObject);
	},
	
	pasteAsChild: function() {
		if(this._jsonObject == null) {
			alert("ERROR: _jsonObject == null");
			return;
		}
		
		var newChildObj = this._cloneObject(this._clipBoard.data);
		
		if(this._hasDupTitle(newChildObj.title, this._currentNode.child)) {
			return;
		}
		
		var path = this._currentKey;
		var arrPath = path.split("-");
		switch(arrPath.length) {
			case 1:
				switch(path.substr(0, 1)) {
					case "A":
						var arrRootKeys = /A(\d+)/.exec(path);
						var ax = arrRootKeys[0] + "";
						newChildObj.key = ax + "-B1";
//						this._currentNode.child.unshift(newChildObj); // new child should be at place 0
						this._currentNode.child.push(newChildObj); // new child should be at the last place
						this._reIndex(this._currentNode.child);
						break;
				}
				break;
			default:
				if((path.length + this._clipBoard.depth) > 26) {
					alert("Level too deep!");
					return;
				}
				
				var arrLastLetter = /([A-Z])/.exec(arrPath[arrPath.length - 1]);
				var lastLetter = arrLastLetter[1];
				if(lastLetter == "Z") {
					alert("No more Child-Node allowed!");
					return;
				}
				var newLetter = String.fromCharCode(lastLetter.charCodeAt(0) + 1);
				newChildObj.key = path + "-" + newLetter + "1";
//				this._currentNode.child.unshift(newChildObj);
				this._currentNode.child.push(newChildObj);
				this._reIndex(this._currentNode.child);
				break;
		}
		this.refreshNavTree(this._jsonObject);
	},
	
	getJSONObject: function() {
		return this._jsonObject;
	},
	
	getFileName: function(node) {
		if(node.title == null) {
			return "";
		}
		
		if(node.alias != "") {
			return node.alias;
		}
		
		var fname = node.title;
		fname = fname.toLowerCase();
		
		fname = fname.replace(/\u00df/g, "ss");
		fname = fname.replace(/\u00e4/g, "ae");
		fname = fname.replace(/\u00f6/g, "oe");
		fname = fname.replace(/\u00fc/g, "ue");
		
		
		fname = fname.replace(/_/g, "-"); 				// "_" to "-"
		fname = fname.replace(/\s/g, "-"); 				// spaces to "-"
		while( /\(.*\)/.test(fname) == true){			// for nested parentheses
			fname = fname.replace(/\([^\(\)]*\)/g, "");	// remove parentheses
		}
		while( /<.*>/.test(fname) == true){
			fname = fname.replace(/<[^<>]*>/g, ""); 	// remove any html tags
		}
		fname = fname.replace(/([^a-z0-9\-])/g, "");	// delete all symbols except (a-z0-9 and "-")
		fname = fname.replace(/-{2,}/g, "-"); 			// max 1 "-" in a row
		fname = fname.replace(/^-+|-+$/g, "");			// remove all '-' at beginning/end
		
		return fname;
	},
	
	// same as getFileName, only for checking and prompt for alias if needed
	getFileName2: function(title) {
		var fname = title;
		fname = fname.toLowerCase();
		
		fname = fname.replace(/\u00df/g, "ss");
		fname = fname.replace(/\u00e4/g, "ae");
		fname = fname.replace(/\u00f6/g, "oe");
		fname = fname.replace(/\u00fc/g, "ue");
		
		
		fname = fname.replace(/_/g, "-"); 				// "_" to "-"
		fname = fname.replace(/\s/g, "-"); 				// spaces to "-"
		while( /\(.*\)/.test(fname) == true){			// for nested parentheses
			fname = fname.replace(/\([^\(\)]*\)/g, "");	// remove parentheses
		}
		while( /<.*>/.test(fname) == true){
			fname = fname.replace(/<[^<>]*>/g, ""); 	// remove any html tags
		}
		fname = fname.replace(/([^a-z0-9\-])/g, "");	// delete all symbols except (a-z0-9 and "-")
		fname = fname.replace(/-{2,}/g, "-"); 			// max 1 "-" in a row
		fname = fname.replace(/^-+|-+$/g, "");			// remove all '-' at beginning/end
		
		return fname;
	},
	
	getPathInfo: function(node) {
		if(node.key == null) {
			return "";
		}
		
		var pkey = node.key;
		var fname = "";
		if(this._getNodeType(pkey) == "A") {
			var arrPKey = pkey.split("-");
			if(arrPKey.length == 1) { // root level
				if(node.child.length > 0) {
					fname = "/" + this.getFileName(node) + "/index.shtml";
				} else {
					fname = "/" + this.getFileName(node) + ".shtml";
				}
			} else {
				var idx0 = arrPKey[0].substr(1) * 1;
				var idx1 = arrPKey[arrPKey.length - 1].substr(1) * 1;
				var locatorGroup = this._jsonNavNormal[idx0].child;
				fname = "/" + this.getFileName(this._jsonNavNormal[idx0]);
				for(var i = 2; i < arrPKey.length; i++) { // start from 3rd...
					idx0 = arrPKey[i - 1].substr(1) * 1;
					fname += "/" + this.getFileName(locatorGroup[idx0 - 1]);
					locatorGroup = locatorGroup[idx0 - 1].child; // root level, number = index, deeper ones must -1
				}
				fname += "/" + this.getFileName(locatorGroup[idx1 - 1]);
				if(node.child.length > 0) {
					fname += "/index.shtml";
				} else {
					fname += ".shtml";
				}
			}
		} else { // Z und S
			fname = "/" + this.getFileName(node) + ".shtml";
		}
		return fname;
	},
	
	addPathInfo: function(tree) {
		for(var i = 0; i < tree.length; i++) {
			tree[i].path = this.getPathInfo(tree[i]);
			if(tree[i].child.length > 0) {
				this.addPathInfo(tree[i].child);
			}
		}
	},
	
	moveUp: function() {
		if(this._jsonObject == null) {
			alert("ERROR: _jsonObject == null");
			return null;
		}
		var pos = 0;
		for(var i = 0; i < this._currentNodeGroup.length; i++) {
			if(this._currentNodeGroup[i].key == this._currentKey) {
				if(i == 0) { // already at top
					break;
				}
				var t = this._currentNodeGroup[i];
				this._currentNodeGroup[i] = this._currentNodeGroup[i - 1];
				this._currentNodeGroup[i - 1] = t;
				break;
			}
			pos++;
		}
		this._reIndex(this._currentNodeGroup);
		
		if(pos > 0) {
			pos -= 1;
		}
		var mk = this._currentNodeGroup[pos].key;
		this.refreshNavTree(this._jsonObject);
		return mk;
	},
	
	moveDown: function() {
		if(this._jsonObject == null) {
			alert("ERROR: _jsonObject == null");
			return null;
		}
		var pos = 0;
		for(var i = 0; i < this._currentNodeGroup.length; i++) {
			if(this._currentNodeGroup[i].key == this._currentKey) {
				if(i == this._currentNodeGroup.length - 1) { // already at bottom
					break;
				}
				var t = this._currentNodeGroup[i];
				this._currentNodeGroup[i] = this._currentNodeGroup[i + 1];
				this._currentNodeGroup[i + 1] = t;
				break;
			}
			pos++;
		}
		this._reIndex(this._currentNodeGroup);
		if(pos < (this._currentNodeGroup.length - 1)) {
			pos += 1;
		}
		var mk = this._currentNodeGroup[pos].key;
		this.refreshNavTree(this._jsonObject);
		return mk;
	}
};
