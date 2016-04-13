/**
 * Loading overlay for ajax requests,<br>
 * just include this .js file
 * @author Dmitry Gorelenkov
 *
 * @TODO settings argument, and modify to named function, for multiple instances
 */


var MyLoadingAjaxOverlay = new function (){
//    this.constructor.prototype.stopAll = function(){
//        $(document).trigger('stopAll', {});
//    };

    if(!jQuery){
        return false;
    }

    var self = this;
    var _loadingImgSrc = "ajax-loader2.gif";
    var _closeImgSrcSrc = "ajax-loading-close.gif";
    var _loading_opacity = 70;


    /**
     * loading image
     * @type {*|jQuery}
     */
    var imgLoading = (
        $('<img/>', {
            //'class': 'tmpLoadingImg',
            'src': _loadingImgSrc,
            'css': {
                'z-index' : 1001,
                'position' : 'fixed',
                'left' : '50%',
                'top' : '50%'
            }
        })
            //after img loaded, set position
            .load(function(){
                $(this).css({
                    'margin-left' : '-'+this.width/2+'px',
                    'margin-top' : '-'+this.height/2+'px'
                });
            })
        );

    /**
     * close button image
     * @type {*|jQuery}
     */
    var imgCloseBtn = (
        $('<img/>', {
            //'class': 'tmpCloseLoadingImg',
            'src': _closeImgSrcSrc,
            'css': {
                'z-index' : 1002,
                'position' : 'fixed',
                'left' : '100%',
                'top' : '0%',
                'cursor' : 'pointer'
            }
        })
            //close on click
            .bind('click', function() {
                divLoadingOverlay.fadeOut(100);
            })
            //after img loaded, set position
            .load(function(){
                $(this).css({
                    'margin-top' : '0px',
                    'margin-left' : '-'+this.width+'px'
                });

            })
        );



    /**
     * main overlay div
     * @type {*|jQuery|HTMLElement}
     */
    var divLoadingOverlay = $( '<div/>', {
        //'class': 'tmpLoadingOverlay',
        'loaded': false,
        'css': {
            'z-index' : 1000,
            'position' : 'fixed',
            'left' : '0%',
            'top' : '0%',
            'height' : '100%',
            'width' : '100%',
            'margin-left' : '0px',
            'margin-top' : '0px',
            'background-color' :  'white',
            'opacity' : _loading_opacity/100,
            '-moz-opacity' : _loading_opacity/100,
            'filter' : 'alpha(opacity='+_loading_opacity+')',
            'visibility' : 'visible',
            'text-align' :  'center'
        }
    });

    //put images to loading overlay
    divLoadingOverlay
        .append(imgLoading)
        .append(imgCloseBtn);

    /**
     * Show or hide loading overlay depends on <b>value</b>
     * @param value 1 or 0 / true or false
     */
    this.loading = function(value){
        if (value){
            //show overlay

            //append to body if not created yet
            if(!divLoadingOverlay.loaded){
                $('body').append(divLoadingOverlay);
                divLoadingOverlay.loaded = true;
            }
            divLoadingOverlay.fadeIn(100);
        }else{
            //hide overlay
            divLoadingOverlay.fadeOut(100);
        }
    };

    //show overlay by ajaxStart
    $(document).ajaxStart(function(){
        self.loading(1);
    });

    //hide overlay by ajaxStop
    $(document).ajaxStop(function(){
        self.loading(0);
    });


//    $(document).bind('stopAll', function(){
//        self.loading(0);
//    });

};