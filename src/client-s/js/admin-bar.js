(function ($) {
  'use strict'; // Standards.

  var plugin = {
      namespace: 'zencache'
    },
    $window = $(window),
    $document = $(document);

  var animationEnd = 'webkitAnimationEnd mozAnimationEnd MSAnimationEnd oanimationend animationend';

  plugin.onReady = function () // DOM ready event handler.
    {
      plugin.hideAJAXResponseTimeout = null;
      plugin.vars = $('#' + plugin.namespace + '-vars').data('json');

      $('#wp-admin-bar-' + plugin.namespace + '-wipe > a').on('click', plugin.wipeCache);
      $('#wp-admin-bar-' + plugin.namespace + '-clear > a').on('click', plugin.clearCache);
      $document.on('click', '#' + plugin.namespace + '-ajax-response', plugin.hideAJAXResponse);
    };

  plugin.clearCache = function () {
    var postVars = {
      _wpnonce: plugin.vars._wpnonce
    }; // HTTP post vars.
    postVars[plugin.namespace] = {
      ajaxClearCache: '1'
    };
    var $clear = $('#wp-admin-bar-' + plugin.namespace + '-clear > a');

    plugin.removeAJAXResponse(); // Remove response w/o delay.
    $clear.parent().addClass(plugin.namespace + '-clearing');
    $clear.attr('disabled', 'disabled'); // Disable.

    $.post(plugin.vars.ajaxURL, postVars, function (data) {
      plugin.removeAJAXResponse(); // Remove response w/o delay.
      $clear.parent().removeClass(plugin.namespace + '-clearing');
      $clear.removeAttr('disabled'); // Re-enable.

      var $response = $('<div id="' + plugin.namespace + '-ajax-response" class="' + plugin.namespace + '-ajax-response-clear">' + data + '</div>');
      $('body').append($response); // Append response.
      plugin.showAJAXResponse(); // Show response.
    });
  };

  plugin.wipeCache = function () {
    var postVars = {
      _wpnonce: plugin.vars._wpnonce
    }; // HTTP post vars.
    postVars[plugin.namespace] = {
      ajaxWipeCache: '1'
    };
    var $wipe = $('#wp-admin-bar-' + plugin.namespace + '-wipe > a');

    plugin.removeAJAXResponse(); // Remove response w/o delay.
    $wipe.parent().addClass(plugin.namespace + '-wiping');
    $wipe.attr('disabled', 'disabled'); // Disable.

    $.post(plugin.vars.ajaxURL, postVars, function (data) {
      plugin.removeAJAXResponse(); // Remove response w/o delay.
      $wipe.parent().removeClass(plugin.namespace + '-wiping');
      $wipe.removeAttr('disabled'); // Re-enable.

      var $response = $('<div id="' + plugin.namespace + '-ajax-response" class="' + plugin.namespace + '-ajax-response-wipe">' + data + '</div>');
      $('body').append($response); // Append response.
      plugin.showAJAXResponse(); // Show response.
    });
  };

  plugin.showAJAXResponse = function () {
    clearTimeout(plugin.hideAJAXResponseTimeout);

    $('#' + plugin.namespace + '-ajax-response').off(animationEnd).on(animationEnd, function () {
      plugin.hideAJAXResponseTimeout = setTimeout(plugin.hideAJAXResponse, 2500);
    }).show().addClass(plugin.namespace + '-animation-zoomInDown');
  };

  plugin.hideAJAXResponse = function (event, animate) {
    clearTimeout(plugin.hideAJAXResponseTimeout);

    $('#' + plugin.namespace + '-ajax-response').off(animationEnd).on(animationEnd, function () {
      plugin.removeAJAXResponse();
    }).addClass(plugin.namespace + '-animation-bounceOutUp');
  };

  plugin.removeAJAXResponse = function () {
    clearTimeout(plugin.hideAJAXResponseTimeout);

    $('#' + plugin.namespace + '-ajax-response').off(animationEnd).remove();
  };

  $document.ready(plugin.onReady); // On DOM ready.

})(jQuery);
