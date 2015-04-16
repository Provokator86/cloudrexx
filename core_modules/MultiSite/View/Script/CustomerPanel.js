var websiteLoginUrl;
var cadminPath;
var customerPanel = {
  messageTypes : ['error', 'warning', 'info', 'success']  
};
cx.ready(function() {
    cadminPath      = cx.variables.get('cadminPath', 'contrexx');
    websiteLoginUrl = cadminPath + 'index.php&cmd=JsonData&object=MultiSite&act=websiteLogin';
    // init all message modal's
    jQuery.each(customerPanel.messageTypes, function( index, value ) {
      jQuery('#'+ value + '_msg_container').modal({backdrop: false, show: false});
    });
});

/**
 * Show messages to the user
 * uses bootstrap modal and predefined modal boxes
 * 
 * @param html    msgTxt         Html format message text
 * @param string  type           Type of message(error, warning, info, success)
 * @param boolean hideAfterDelay Hide the modal after a delay, default true
 */
function showMessage(msgTxt, type, hideAfterDelay) {    
    type           = jQuery.inArray(type, customerPanel.messageTypes) !== -1 ? type : 'error';
    hideAfterDelay = typeof hideAfterDelay !== 'undefined' ? hideAfterDelay : true;
    
    // hide all message modals
    jQuery.each(customerPanel.messageTypes, function( index, value ) {      
      jQuery('#'+ value + '_msg_container').modal('hide');
    });
    
    $objModal = jQuery('#'+ type + '_msg_container');
    $content  = $objModal.find('.msg_text');
    
    $content.html(msgTxt);
    $objModal.modal('show');

    if (hideAfterDelay) {
      setTimeout(function() {$objModal.modal('hide');}, 2000);
    }
}

function getQueryParams(qs) {
    qs = qs.split("+").join(" ");
    var params = {},
        tokens,
        re = /[?&]?([^=]+)=([^&]*)/g;

    while (tokens = re.exec(qs)) {
        params[decodeURIComponent(tokens[1])] = decodeURIComponent(tokens[2]);
    }

    return params;
}

function updateQueryStringParameter(key, value, uri) {
  if (!uri) uri = window.location.href;
  var re = new RegExp("([?&])" + key + "=.*?(&|$)", "i");
  var separator = uri.indexOf('?') !== -1 ? "&" : "?";
  if (uri.match(re)) {
    return uri.replace(re, '$1' + key + "=" + value + '$2');
  } else {
    return uri + separator + key + "=" + value;
  }
}

var requsts = new Array();
function  loadContent(jQuerySelector, url) {
    jQuery.ajax({
        dataType: 'html',
        url: url,
        type: 'GET',
        beforeSend: function (xhr, settings) {
            var loadingDiv = jQuery('<div />')
                                .html('<img src="/lib/javascript/jquery/jstree/themes/default/throbber.gif" /> ' + cx.variables.get('loadingText', 'multisite/lang'));
            if (jQuery.trim(jQuery(jQuerySelector+ ' table').html()).length > 1) {
                showAnimationImageInReloadContent(loadingDiv, jQuerySelector);
            } else {
                var selector = jQuerySelector;
                if (jQuery(jQuerySelector + ' div.grid-offset').length > 0) {
                    selector = selector + ' div.grid-offset';
                }
                jQuery(selector).append(loadingDiv);
            }
            
        },
        success: function(data) {
            if (data) {
                jQuery(jQuerySelector).html(data);
            }
        },
        error: function(xhr, textStatus, errorThrown) {
            if(requsts[this.url] === undefined ) {
                requsts[this.url] = 1;
            }
            if (xhr.status.toString().match(/^5\d{2}$/)  && requsts[this.url] < 3) {
                requsts[this.url] += 1;
                jQuery.ajax(this);
            }
        }
    });
}

/**
 * Show animation image when refresh a content
 * 
 * @param object loadingDiv
 * @param string jQuerySelector
 * 
 * @returns null
 */
function showAnimationImageInReloadContent(loadingDiv, jQuerySelector) {
    loadingDiv.addClass('loading_span');
    $contentTable = jQuery(jQuerySelector).find('table');
    $contentTable.addClass('loadlockRelative');
    $contentTable.append('<div class="load-lock"></div>');
    jQuery(jQuerySelector+' .load-lock').show();
    $contentTable.append(loadingDiv);
}

/**
 * Generate Remote website-login token
 * depends bootstrap js
 * 
 * @param object elm jQuery button object
 */
function getRemoteLoginToken($this) {
    var websiteId = $this.data('id');
    
    jQuery.ajax({
        dataType: "json",
        url: websiteLoginUrl,
        data: {
            websiteId :  websiteId
        },
        type: "POST",
        beforeSend: function (xhr, settings) {
            $this.button('loading');
            $this.prop('disabled', true);
            checkAddClass($this, 'remove');
        },
        success: function(response) {
            if (response.status == 'success') {
                resp = response.data;
                if (resp.status == 'success') {
                    var newWindow = window.open(resp.webSiteLoginUrl, '_blank');
                    if(newWindow) {
                        //Browser has allowed it to be opened
                        newWindow.focus();
                    }
                } else {
                    showMessage(resp.message, 'error');
                }
            } else {
                showMessage(response.message, 'error');
            }
        },
        complete: function (xhr, settings) {
            $this.button('reset');
            $this.prop('disabled', false);
            checkAddClass($this, 'add');
        },
        error: function() { }
    });   
}
    
/**
 * Enable | Disable mail service
 * 
 * @param object elm jQuery button object
 */
function enableOrDisableMailService($this) {
    var act = $this.data('act');
    var url = cadminPath + 'index.php&cmd=JsonData&object=MultiSite&act='+act;    
    var websiteId = $this.data('id');
    var message = '';

    jQuery.ajax({
        dataType: "json",
        url: url,
        data: {
            websiteId :  websiteId
        },
        type: "POST",
        beforeSend: function (xhr, settings) {
            $this.button('loading');
            $this.prop('disabled', true);
            checkAddClass($this, 'remove');
        },
        success: function(response) {
            if (response.status == 'success') {
                resp = response.data;
                if (resp.status == 'success') {
                    showMessage(resp.message, 'success');
                    loadContent('#multisite_website_email', '/api/MultiSite/Website/Email?id=' + websiteId);
                } else {
                    showMessage(resp.message, 'error');
                }
            } else {
                showMessage(response.message, 'error');
            }
        },
        complete: function (xhr, settings) {
            $this.button('reset');
            $this.prop('disabled', false);
            checkAddClass($this, 'add');
        },
        error: function() { }
    });
}

/**
 * validate AffiliateId
 */
function checkAvailablityOfAffiliateId(affiliateId) {
    var url        = cadminPath + 'index.php&cmd=JsonData&object=MultiSite&act=checkAvailabilityOfAffiliateId',
        errorBlock = jQuery('#setAffiliateId .errorBlock');
    
    lastXhr = jQuery.ajax({
        dataType: "json",
        url: url,
        data: {
            affiliateProfileAttributeId :  affiliateId
        },
        type: "POST",
        beforeSend: function (xhr, settings) {
            jQuery(".save").prop('disabled', true);
        },
        success: function(response, status, xhr) {
            if (xhr === lastXhr) {
                var errMsg = false;
                if (response.status == 'success') {
                    resp = response.data;
                    if (resp.status == 'success') {
                        hideErrorBlock(errorBlock);
                    } else {
                        errMsg = resp.message;                                              
                    }
                } else {
                    errMsg = response.message;                                     
                }
                if (errMsg !== false) {
                    showErrorBlock(errorBlock, errMsg);
                }
            }
        },
        complete: function (xhr, settings) {
            enableOrDisableButton();
            jQuery(".save").button('reset');
        },
        error: function() { }
    });
}

function isValidEmail(mailId) {
    var pattern = new RegExp(/^[+a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,4}$/i);
    
    if (pattern.test(mailId) === false) {
        return false;
    }
    return true;
}

/**
 * Save AffiliateId and PayPal mail address
 * 
 * @param object elm jQuery button object
 */
function saveAffiliateIdAndPayPalMailAddress($this) {
    if (   (   jQuery("#affiliateProfileAttributeId").length
            && jQuery("#paypalEmail").val() === ''
            && jQuery("#affiliateProfileAttributeId").val() === ''
            )
            || (   !jQuery("#affiliateProfileAttributeId").length 
                && jQuery("#paypalEmail").val() === ''
               )
        ) {
        $this.prop('disabled', true);
        showMessage(cx.variables.get('TXT_CORE_MODULE_MULTISITE_NO_INPUT_ERROR', 'AffiliateSetup'), 'error');
        return false;
    }
    
    if (jQuery("#paypalEmail").length && jQuery("#paypalEmail").val() !== '' && !isValidEmail(jQuery("#paypalEmail").val())) {
        showErrorBlock(jQuery('#paypalAccountAddress .errorBlock'), cx.variables.get('TXT_CORE_MODULE_MULTISITE_PAYPAL_EMAIL_ADDRESS_ERROR', 'AffiliateSetup'));
        $this.prop('disabled', true);
        return false;
    }

    var data = jQuery( "form" ).serialize();
    jQuery.ajax({
        dataType: "json",
        url: cadminPath + 'index.php&cmd=JsonData&object=MultiSite&act=setAffiliate',
        data: data,
        type: "POST",
        beforeSend: function (xhr, settings) {
            hideErrorBlock(jQuery('.errorBlock'));
            $this.button('loading');
            $this.prop('disabled', true);
        },
        success: function(response) {
            var errMsg = false;
            var errorBlock = '';
            if (response.status == 'success') {
                resp = response.data;
                if (resp.status == 'success') {
                    showMessage(resp.message, 'success');
                    window.location.reload();
                } else {
                    errorBlock = (resp.type == 'mail') ? jQuery('#paypalAccountAddress .errorBlock') : jQuery('#setAffiliateId .errorBlock');
                    errMsg = resp.message;
                }
            } else {
                errorBlock = (response.type == 'mail') ? jQuery('#paypalAccountAddress .errorBlock') : jQuery('#setAffiliateId .errorBlock');
                errMsg = response.message; 
            }
            if (errMsg !== false) {
                showErrorBlock(errorBlock, errMsg);
            }
        },
        complete: function (xhr, settings) {
            $this.button('reset');
        },
        error: function() { }
    });
}

function showErrorBlock(errorBlock, errMsg) {
    errorBlock
        .addClass('has-error')
        .html('<div class="help-block" role="alert">' + errMsg + '</div>');
}

function hideErrorBlock(errorBlock) {
    errorBlock
        .removeClass('has-error')
        .html('&nbsp;');
}

function enableOrDisableButton() {
    if (jQuery(".errorBlock .help-block").length === 0) {
        jQuery(".save").prop('disabled', false);
    } else {
        jQuery(".save").prop('disabled', true);
    }
}

function pleskAutoLogin($this) {
      var url = cadminPath + 'index.php&cmd=JsonData&object=MultiSite&act=getPanelAutoLoginUrl';
      var data = {websiteId: $this.data('id')};
      requestAutoLogin($this, url, data);
}

function showRemoteModal(options) {
  var defaultOpts = {
    modalId : '',
    remoteUrl : '',
    show: function(e) {},
    shown: function() {},
    hide: function() {},
    hidden: function() {}
  };
  var opts = jQuery.extend({}, defaultOpts, options);
  
  if (!jQuery('#'+ opts.modalId).length) {
    return;
  }
  
  if (opts.remoteUrl == '') {
    return;
  }
  
  jQuery('#'+ opts.modalId + ' .modal-content')
    .html(
      jQuery('<div />')
        .addClass('grid-elm grid-align-1-1 grid-offset')
        .html('<img src="/lib/javascript/jquery/jstree/themes/default/throbber.gif" /> ' + cx.variables.get('loadingText', 'multisite/lang'))
    );
  jQuery('#'+ opts.modalId)
    .on("show.bs.modal",
      function() {
        opts.show();
      }
    ).on("shown.bs.modal",
      function() {
        opts.shown();
      }
    ).on("hide.bs.modal",
      function() {
        opts.hide();
      }
    ).on("hidden.bs.modal",function() {
      opts.hidden();
      jQuery(this).unbind();
      jQuery(this).data('bs.modal', null);
    }).modal({
      remote : opts.remoteUrl
    });
}

function sendApiFormRequest(jsFormSelector, jsModalSelector, loadContentSelector, apiUrl, callbackOptions) {
  if (!jsFormSelector || !jsModalSelector || !loadContentSelector || !apiUrl) {
    return;
  }
  
  jQuery.ajax({
    dataType: 'json',
    url: jQuery(jsFormSelector).attr('action'),
    data: jQuery(jsFormSelector).serialize(),
    type: "POST",
    beforeSend: function (xhr, settings) {
        if (callbackOptions !== 'undefined') {
            callbackOptions.beforesend(jQuery(jsFormSelector + ' .loadingProcess'));              
        } else {
            jQuery('.loadingProcess').button('loading');
            jQuery('.loadingProcess').prop('disabled', true);
            jQuery('.loadingProcess').removeClass('save');
        }
    },
    success: function (response) {
      message = (response.status == 'success') ? response.data.message : (jQuery.type(response.message) === 'object') 
                                                                         ? response.message.message
                                                                         : response.message;
      
      jQuery(jsModalSelector).on('hidden.bs.modal', function () {
        showMessage(message, response.status);//show status message 
      });
      jQuery(jsModalSelector).modal('hide');
      if (response.status == 'success') {
          if (callbackOptions !== 'undefined') {
              callbackOptions.callback();              
          } else {
            loadContent(loadContentSelector, apiUrl);
          }
      }
    },
    complete: function (xhr, settings) {
        if (callbackOptions !== 'undefined') {
            callbackOptions.complete(jQuery(jsFormSelector + ' .loadingProcess'));              
        } else {
            jQuery('.loadingProcess').button('reset');
            jQuery('.loadingProcess').prop('disabled', false);
            jQuery('.loadingProcess').addClass('save');
        }
    },
    fail: function (response) {
    }
  });

}

function payrexxAutoLogin($this) {
  var url = cadminPath + 'index.php&cmd=JsonData&object=MultiSite&act=payrexxAutoLoginUrl';
  var data = {};
  requestAutoLogin($this, url, data);
}

function checkAddClass(element, task){
    if(task = 'remove'){
        restoreAddClass = false;
        if(element.hasClass('add')){
            restoreAddClass = true;
            element.removeClass('add');
        }
    }else if(task = 'add'){
        if(restoreAddClass){
            element.addClass('add');
        }
    }
}

function requestAutoLogin($this, url, data) {
    jQuery.ajax({
        dataType: "json",
        url: url,
        data: data,
        type: "POST",
        beforeSend: function (xhr, settings) {
            $this.button('loading');
            $this.prop('disabled', true);
            checkAddClass($this, 'remove');
        },
        success: function(response) {
            if (response.status == 'success') {
                switch(response.data.status) {
                    case 'success':
                        window.open(response.data.autoLoginUrl, '_blank');
                        break;
                    case 'error':
                        showMessage(response.data.message, 'error');
                        break;
                    default:
                        break;
                }
            } else {
                showMessage(response.message, 'error');
            }
        },
        complete: function (xhr, settings) {
            $this.button('reset');
            $this.prop('disabled', false);
            checkAddClass($this, 'add');
        },
        error: function() { }
    });
}

jQuery('body').on('click', '.remoteModal', function() {
  showRemoteModal({
    modalId   : jQuery(this).data('target'),
    remoteUrl : jQuery(this).data('remote')    
  });
});

function addTableSorting(sorterTables) {
    jQuery.each(sorterTables, function(i, v) {
        var headers = new Object();
        jQuery('.' + v + ' th').each(function(index, elm){
            if(jQuery(this).hasClass('noSorting')) {
                headers[index] = {sorter : false};
            }
        });
        var headerSetting = {headers: headers};
        jQuery('.' + v ).tablesorter(headerSetting);
    });
}