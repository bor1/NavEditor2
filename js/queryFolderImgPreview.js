function folderImgPreview(o){
    if(!jQuery){return false;}

    // Defaults
    if( !o ) var o = {};
    if( o.ImgPrevContainer == undefined ){ return false };
    if( o.loadedFolder == undefined ) o.loadedFolder = '/';
    if( o.script == undefined ) o.script = 'app/folderImgPreview.php';
    if( o.loadMessage == undefined ) o.loadMessage = 'Loading...';
    if( o.defaultRadioChecked == undefined ) o.defaultRadioChecked = "showall"
    if( o.loadedCallback == undefined ) o.loadedCallback = function(){return true;};
    if( o.clickPicCallback == undefined ) o.loadedCallback = function(){return true;};
    if( o.picsArray == undefined ) o.picsArray = new Array();
    if( o.loadByInit == undefined ) o.loadByInit = true;
    if( o.thumbNames == undefined ) o.thumbNames = 'thumb_';
    if( o.filterSpeed == undefined ) o.filterSpeed = 300;

    //options vars
    o.headContainer;
    o.picsContainer;
    o.radioButton;
    o.currentCheckedRadioButton;
    o.defaultRadioChecked;

    function constructContainer(){
        //create and save main containers
        o.ImgPrevContainer.html("");
        o.ImgPrevContainer.html('<div id="folderImgPreview_headerContainer"></div>\n\
                                <div id="folderImgPreview_picsContainer"></div>');

        o.headContainer = o.ImgPrevContainer.find("#folderImgPreview_headerContainer");
        o.picsContainer = o.ImgPrevContainer.find("#folderImgPreview_picsContainer");

        //construct Header
        o.headContainer.append('<input type="radio" name="thumbRadioButtons" value="showall"/><label for="thumbRadioButtons">Alle bilder zeigen</label>');
        o.headContainer.append('<input type="radio" name="thumbRadioButtons" value="noThumbs"/><label for="thumbRadioButtons">Thumbs nicht anzeigen</label>');
        o.headContainer.append('<input type="radio" name="thumbRadioButtons" value="thumbsOnly"/><label for="thumbRadioButtons">Nur Thumbs anzeigen</label>');
        o.radioButton = o.headContainer.find('input[name="thumbRadioButtons"]');

        //check default radio button
        o.currentCheckedRadioButton = o.headContainer.find('input[name="thumbRadioButtons"][value="'+o.defaultRadioChecked+'"]')
        o.currentCheckedRadioButton.attr("checked","checked");

        //bind event on radiobutton change
        o.radioButton.bind("change", function(){
            o.currentCheckedRadioButton = o.radioButton.filter(':checked')
            fillContainer();
        });
    }



    //links von php laden,  .. auf ajax warten? wie?
    function getLinks(){
        //TODO
    }

    //container mit html data fuellen
    function fillContainer(imagesArray){
        o.picsContainer.fadeOut(300, function(){
            cleanContainer();
            //entsprechent einstellungen bilder laden/filtrieren
            var filteredArrayOfPics = filterImgsBySettings(imagesArray);
            var htmlWithImages = "";

            //mit jedem Bild HTML generieren
            for(var linkIdx in filteredArrayOfPics){
                htmlWithImages += createImgHtml(filteredArrayOfPics[linkIdx]);
            }
            //generierte HTML hinzufuegen
            o.picsContainer.append(htmlWithImages);
            //pro bild, click event binden, mit callBack function
            o.picsContainer.find('.divPicContainer').bind('click', o.clickPicCallback)
                            .hover(function(){
                                $(this).css({'background-color':'#bbcffa','cursor':'pointer'});},
                                function(){
                                $(this).css({'background-color':'#fff','cursor':'auto'});}
                            );
            //html anzeigen
            o.picsContainer.fadeIn(500);
        });
    }

    //array of pics, abhaengig von radiobutton filtern, und zuruckgeben
    function filterImgsBySettings(arrayOfImgs){
        var returnArray;

        //save copy of array to localArray
        if(arrayOfImgs == null){
            returnArray = o.picsArray.slice(0);
        }else{
            returnArray = arrayOfImgs.slice(0);
        }

        //array filtern und zuruckgeben, je nach RADIO button value
        switch(o.currentCheckedRadioButton.val()){
            case "noThumbs":
                return filterPicsArray(returnArray, "hasNot", o.thumbNames);
                break;
            case "thumbsOnly":
                return filterPicsArray(returnArray, "has", o.thumbNames);
                break;
            default:
                return returnArray
        }
    }

    //bilder array filtern, zuruckgeben
    function filterPicsArray(picsArray, filterTyp, filterString){
        var filterFunc;
        switch (filterTyp.toLowerCase()){

            case "is":
                filterFunc = function(element/*String*/){
                    return element == filterString;
                }
                break;

            case "has":
                filterFunc = function(element /*String*/){
                    return element.indexOf(filterString) > -1;
                }
                break;

            case "hasNot".toLowerCase():
                filterFunc = function(element /*String*/){
                    return element.indexOf(filterString) == -1;
                }
                break;

            default:
                filterFunc = function(element /*String*/){
                    return true;
                }
        }

        return $.grep(picsArray, filterFunc);
    }



    //html fuer img link generieren
    //TODO mit css object, und options
    function createImgHtml(link){
        return '<div class = "divPicContainer" style="height:150px; width: 150px; border-width: 1px; border-style:solid; float: left;">\n\
                    <img style="display: block; margin-top: 5px; margin-left: auto; margin-right: auto; max-height: 140px; max-width: 140px;" alt="Preview"\\n\
                     src="'+link+'" title="'+link+'">\n\
                </div>\n';
    }

    //container leeren
    function cleanContainer(){
        o.picsContainer.html("");
    }

    //fehlerbehandlung, einfach in container ausgeben
    function errorHandler(errorObj){
        try{
           o.picsContainer.html(errorObj.message);
        }catch(e){}
    }

    //pruefen ob pfad ist ein bild
    function isPic(path){
        var toArray = path.split(".");

        var ext = toArray[toArray.length-1];

        if($.inArray(ext, new Array("jpg","jpeg","gif","png")) != -1){
            return true;
        }
        return false;

    }

    //einfach daten von PHP laden, by verzeichnis
    function loadPicsArrayByPath(path, callbackFn){

        var parsedData = Array();

        $.post(o.script,  {dir: path}, function(data){
            parsedData = JSON.parse(data);
            callbackFn(parsedData);
            });
    }

    //extract verzeichnis von path
    function verzeichnis(path){
        var str = path + '';
        var slash = str.lastIndexOf('/');
        return str.substr(0, slash+1);
    }

    //PUBLIC FUNCS

    //refresh pics by new path
    this.loadByPath = function(path){
        if(typeof path == "string" && path.length > 0){
            o.loadedFolder = path;
        }

        //loading message..
        o.picsContainer.html(o.loadMessage);

        loadPicsArrayByPath(path, function(parsedData){
            try{
                o.picsArray = parsedData;
                if(o.picsArray == null ) o.picsArray = new Array();
                fillContainer(o.picsArray);
            }catch(e){
                errorHandler(e);
            }finally{
                //callback anyway
                o.loadedCallback(o.loadedFolder);
            }
        });


    }



    //nur 1 bild laden
    this.loadOnePic = function(path){
        //falls ist ein bild
        if(isPic(path)){

            //falls nicht aktuelle verzeichnis
            if ($.inArray(path, o.picsArray) == -1){
                try{
                    //daten von verzeichnis lokal speichern
                    var verzPath = verzeichnis(path)
                    loadPicsArrayByPath(verzPath, function(parsedData){
                        if(typeof path == "string" && verzPath.length > 0){
                            o.loadedFolder = verzPath;
                        }

                        o.picsArray = parsedData;
                        if(o.picsArray == null ) o.picsArray = new Array();
                    });
                }catch(e){
                    errorHandler(e);
                }
            }

            fillContainer(new Array(path));
            o.loadedCallback(o.loadedFolder);

        //falls kein bild, empty laden
        }else{
            fillContainer(new Array());
        }
    }

    //bilder visuell filtern, nur die anzeigen, die in allowArray vorkommen
    this.filterPicsView = function(allowArray){
        o.picsContainer.find('.divPicContainer').each(function(){
            //falls ok, show
            if ($.inArray(($(this).find('IMG').attr('src')),allowArray) != -1){
                $(this).fadeIn(o.filterSpeed);
            //falls passt nicht, hide
            }else{
                $(this).fadeOut(o.filterSpeed);
            }
        });
    }


    //start functions...
    //container bilden
    constructContainer();

    //falls loadByInit, daten laden
    if(o.loadByInit){
        this.loadByPath(o.loadedFolder);
    }

}


