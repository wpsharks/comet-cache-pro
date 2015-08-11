(function ($) {
  'use strict'; // Standards.

  var plugin = {
      namespace: 'zencache'
    },
    $window = $(window),
    $document = $(document);

  var animationEnd = // All vendor prefixes.
    'webkitAnimationEnd mozAnimationEnd MSAnimationEnd' +
    ' oanimationend animationend';

  plugin.onReady = function () // DOM ready event handler.
    {
      plugin.hideAJAXResponseTimeout = null;
      plugin.vars = $('#' + plugin.namespace + '-vars').data('json');

      $('#wp-admin-bar-' + plugin.namespace + '-wipe > a').on('click', plugin.wipeCache);
      $('#wp-admin-bar-' + plugin.namespace + '-clear > a').on('click', plugin.clearCache);
      $document.on('click', '.' + plugin.namespace + '-ajax-response', plugin.hideAJAXResponse);
    };

  plugin.wipeCache = function () {
    var postVars = {
      _wpnonce: plugin.vars._wpnonce
    }; // HTTP post vars.
    postVars[plugin.namespace] = {
      ajaxWipeCache: '1'
    };
    var $wipe = $('#wp-admin-bar-' + plugin.namespace + '-wipe > a');

    plugin.removeAJAXResponse();
    $wipe.parent().addClass('-wiping');
    $wipe.attr('disabled', 'disabled');

    $.post(plugin.vars.ajaxURL, postVars, function (data) {
      plugin.removeAJAXResponse();
      $wipe.parent().removeClass('-wiping');
      $wipe.removeAttr('disabled');

      var $response = $('<div class="' + plugin.namespace + '-ajax-response -wipe">' + data + '</div>');
      $('body').append($response); // Append response.
      plugin.showAJAXResponse(); // Show response.
    });
  };

  plugin.clearCache = function () {
    var postVars = {
      _wpnonce: plugin.vars._wpnonce
    }; // HTTP post vars.
    postVars[plugin.namespace] = {
      ajaxClearCache: '1'
    };
    var $clear = $('#wp-admin-bar-' + plugin.namespace + '-clear > a');

    plugin.removeAJAXResponse();
    $clear.parent().addClass('-clearing');
    $clear.attr('disabled', 'disabled');

    $.post(plugin.vars.ajaxURL, postVars, function (data) {
      plugin.removeAJAXResponse();
      $clear.parent().removeClass('-clearing');
      $clear.removeAttr('disabled');

      var $response = $('<div class="' + plugin.namespace + '-ajax-response -clear">' + data + '</div>');
      $('body').append($response); // Append response.
      plugin.showAJAXResponse(); // Show response.
    });
  };

  plugin.showAJAXResponse = function () {
    clearTimeout(plugin.hideAJAXResponseTimeout);

    $('.' + plugin.namespace + '-ajax-response').off(animationEnd).on(animationEnd, function () {
      plugin.hideAJAXResponseTimeout = setTimeout(plugin.hideAJAXResponse, 2500);
    }).addClass(plugin.namespace + '-animation-zoom-in-down').show()

    .on('mouseover', function () { // Do not auto-hide if hovered.
      clearTimeout(plugin.hideAJAXResponseTimeout);
      $(this).addClass('-hovered');
    });
  };

  plugin.hideAJAXResponse = function (event, animate) {
    clearTimeout(plugin.hideAJAXResponseTimeout);

    $('.' + plugin.namespace + '-ajax-response').off(animationEnd).on(animationEnd, function () {
      plugin.removeAJAXResponse();
    }).addClass(plugin.namespace + '-animation-bounce-out-up');
  };

  plugin.removeAJAXResponse = function () {
    clearTimeout(plugin.hideAJAXResponseTimeout);

    $('.' + plugin.namespace + '-ajax-response').off(animationEnd).remove();
  };

  $document.ready(plugin.onReady); // On DOM ready.

})(jQuery);
