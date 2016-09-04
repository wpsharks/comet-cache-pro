(function ($) {
  'use strict'; // Standards.

  var plugin = {
      namespace: 'comet_cache'
    },
    $window = $(window),
    $document = $(document);

  plugin.onReady = function () {
    /*[pro strip-from="lite"]*/
    plugin.statsData = null;
    plugin.statsRunning = false;
    /*[/pro]*/
    plugin.hideAJAXResponseTimeout = null;
    plugin.vars = $('#' + plugin.namespace + '-admin-bar-vars').data('json');

    $('#wp-admin-bar-' + plugin.namespace + '-wipe > a').on('click', plugin.wipeCache);
    $('#wp-admin-bar-' + plugin.namespace + '-clear > a').on('click', plugin.clearCache);
    /*[pro strip-from="lite"]*/
    $('#wp-admin-bar-' + plugin.namespace + '-clear-options-wrapper .-home-url-only > a').on('click', plugin.clearCacheHomeUrlOnly);
    $('#wp-admin-bar-' + plugin.namespace + '-clear-options-wrapper .-current-url-only > a').on('click', plugin.clearCacheCurrentUrlOnly);
    $('#wp-admin-bar-' + plugin.namespace + '-clear-options-wrapper .-specific-url-only > a').on('click', plugin.clearCacheSpecificUrlOnly);
    $('#wp-admin-bar-' + plugin.namespace + '-clear-options-wrapper .-opcache-only > a').on('click', plugin.clearCacheOpCacheOnly);
    $('#wp-admin-bar-' + plugin.namespace + '-clear-options-wrapper .-cdn-only > a').on('click', plugin.clearCacheCdnOnly);
    $('#wp-admin-bar-' + plugin.namespace + '-clear-options-wrapper .-transients-only > a').on('click', plugin.clearExpiredTransientsOnly);
    /*[/pro]*/
    $document.on('click', '.' + plugin.namespace + '-ajax-response', plugin.hideAJAXResponse);
    /*[pro strip-from="lite"]*/
    var $stats = $('#wp-admin-bar-' + plugin.namespace + '-stats'),
      $statsWrapper = $stats.find('.-wrapper'),
      $statsContainer = $statsWrapper.find('.-container');

    if ($stats.length && plugin.MutationObserver) { // Possible?
      (new plugin.MutationObserver(function (mutations) {
        $.each(mutations, function (index, mutation) {
          if (mutation.type !== 'attributes') {
            return; // Not applicable.
          }
          if (mutation.attributeName !== 'class') {
            return; // Not applicable.
          }
          var oldValue = mutation.oldValue, // Provided by event.
            newValue = $(mutation.target).prop(mutation.attributeName);

          if (!/\bhover\b/i.test(oldValue) && /\bhover\b/i.test(newValue)) {
            plugin.stats(); // Received `hover` class.
          }
          return false; // Stop iterating now.
        });
      }))
      .observe($stats[0], {
        attributes: true,
        attributeOldValue: true,
        childList: true,
        characterData: true
      }); // See: <http://jas.xyz/1JlzCdi>
    }
    /*[/pro]*/
  };

  plugin.wipeCache = function (event) {
    plugin.preventDefault(event);
    plugin.statsData = null;

    var postVars = {
      _wpnonce: plugin.vars._wpnonce
    }; // HTTP post vars.
    postVars[plugin.namespace] = {
      ajaxWipeCache: '1'
    };
    var $wipe = $('#wp-admin-bar-' + plugin.namespace + '-wipe > a');
    var $clearOptionsLabel = $('#wp-admin-bar-' + plugin.namespace + '-clear-options-wrapper .-label');
    var $clearOptions = $('#wp-admin-bar-' + plugin.namespace + '-clear-options-wrapper .-options');

    plugin.removeAJAXResponse();
    $wipe.parent().addClass('-processing');
    $wipe.add($clearOptions.find('a')).attr('disabled', 'disabled');

    $.post(plugin.vars.ajaxURL, postVars, function (data) {
      plugin.removeAJAXResponse();
      $wipe.parent().removeClass('-processing');
      $wipe.add($clearOptions.find('a')).removeAttr('disabled');

      var $response = $('<div class="' + plugin.namespace + '-ajax-response -wipe">' + data + '</div>');
      $('body').append($response); // Append response.
      plugin.showAJAXResponse(); // Show response.
    });
  };

  plugin.clearCache = function (event, options) {
    plugin.preventDefault(event);
    /*[pro strip-from="lite"]*/
    plugin.statsData = null;

    options = options || {};
    var o = $.extend({}, {
      urlOnly: '',
      opCacheOnly: false,
      cdnOnly: false,
      transientsOnly: false
    }, options);
    /*[/pro]*/

    var postVars = {
      _wpnonce: plugin.vars._wpnonce
    }; // HTTP post vars.

    var isClearOption = false;
    /*[pro strip-from="lite"]*/
    if (o.urlOnly) {
      isClearOption = true;
      postVars[plugin.namespace] = {
        ajaxClearCacheUrl: o.urlOnly
      };
    } else if (o.opCacheOnly) {
      isClearOption = true;
      postVars[plugin.namespace] = {
        ajaxClearOpCache: '1'
      };
    } else if (o.cdnOnly) {
      isClearOption = true;
      postVars[plugin.namespace] = {
        ajaxClearCdnCache: '1'
      };
    } else if (o.transientsOnly) {
      isClearOption = true;
      postVars[plugin.namespace] = {
        ajaxClearExpiredTransients: '1'
      };
    } else
     /*[/pro]*/
     {
      postVars[plugin.namespace] = {
        ajaxClearCache: '1'
      };
    }
    var $clear = $('#wp-admin-bar-' + plugin.namespace + '-clear > a');
    /*[pro strip-from="lite"]*/
    var $clearOptionsLabel = $('#wp-admin-bar-' + plugin.namespace + '-clear-options-wrapper .-label');
    var $clearOptions = $('#wp-admin-bar-' + plugin.namespace + '-clear-options-wrapper  .-options');
    /*[/pro]*/
    plugin.removeAJAXResponse();

    if (isClearOption && $clearOptionsLabel.length) {
      $clearOptionsLabel.addClass('-processing');
    } else {
      $clear.parent().addClass('-processing');
    }
    $clear.add($clearOptions.find('a')).attr('disabled', 'disabled');

    $.post(plugin.vars.ajaxURL, postVars, function (data) {
      plugin.removeAJAXResponse();

      if (isClearOption && $clearOptionsLabel.length) {
        $clearOptionsLabel.removeClass('-processing');
      } else {
        $clear.parent().removeClass('-processing');
      }
      $clear.add($clearOptions.find('a')).removeAttr('disabled');

      var $response = $('<div class="' + plugin.namespace + '-ajax-response -clear">' + data + '</div>');
      $('body').append($response); // Append response.
      plugin.showAJAXResponse(); // Show response.
    });
  };
  /*[pro strip-from="lite"]*/
  plugin.clearCacheHomeUrlOnly = function (event) {
    plugin.clearCache(event, {
      urlOnly: 'home'
    });
  };

  plugin.clearCacheCurrentUrlOnly = function (event) {
    plugin.clearCache(event, {
      urlOnly: document.URL
    });
  };

  plugin.clearCacheSpecificUrlOnly = function (event) {
    var url = $.trim(prompt(plugin.vars.i18n.enterSpecificUrl, 'http://'));

    if (url && url !== 'http://') {
      plugin.clearCache(event, {
        urlOnly: url
      });
    } else {
      plugin.preventDefault(event);
    }
  };

  plugin.clearCacheOpCacheOnly = function (event) {
    plugin.clearCache(event, {
      opCacheOnly: true
    });
  };

  plugin.clearCacheCdnOnly = function (event) {
    plugin.clearCache(event, {
      cdnOnly: true
    });
  };

  plugin.clearExpiredTransientsOnly = function (event) {
    plugin.clearCache(event, {
      transientsOnly: true
    });
  };
  /*[/pro]*/

  plugin.showAJAXResponse = function () {
    clearTimeout(plugin.hideAJAXResponseTimeout);

    $('.' + plugin.namespace + '-ajax-response')
      .off(plugin.animationEndEvents) // Reattaching below.
      .on(plugin.animationEndEvents, function () { // Reattach.
        plugin.hideAJAXResponseTimeout = setTimeout(plugin.hideAJAXResponse, 2500);
      })
      .addClass(plugin.namespace + '-admin-bar-animation-zoom-in-down').show()
      .on('mouseover', function () { // Do not auto-hide if hovered.
        clearTimeout(plugin.hideAJAXResponseTimeout);
        $(this).addClass('-hovered');
      });
  };

  plugin.hideAJAXResponse = function (event) {
    plugin.preventDefault(event);

    clearTimeout(plugin.hideAJAXResponseTimeout);

    $('.' + plugin.namespace + '-ajax-response')
      .off(plugin.animationEndEvents) // Reattaching below.
      .on(plugin.animationEndEvents, function () { // Reattach.
        plugin.removeAJAXResponse(); // Remove completely.
      })
      .addClass(plugin.namespace + '-admin-bar-animation-zoom-out-up');
  };

  plugin.removeAJAXResponse = function () {
    clearTimeout(plugin.hideAJAXResponseTimeout);

    $('.' + plugin.namespace + '-ajax-response')
      .off(plugin.animationEndEvents).remove();
  };
  /*[pro strip-from="lite"]*/
  plugin.stats = function () {
    if (plugin.statsRunning) {
      return; // Still running.
    }
    plugin.statsRunning = true;

    var canSeeMore = !plugin.vars.isMultisite ||
      plugin.vars.currentUserHasNetworkCap;

    var $body = $('body'), // Needed below.

      $stats = $('#wp-admin-bar-' + plugin.namespace + '-stats'),

      $wrapper = $stats.find('.-wrapper'),
      $container = $wrapper.find('.-container'),

      $refreshing = $container.find('.-refreshing'),

      $chartA = $container.find('.-chart-a'),
      $chartB = $container.find('.-chart-b'),

      $totals = $container.find('.-totals'),
      $totalFiles = $totals.find('.-files'),
      $totalSize = $totals.find('.-size'),
      $totalDir = $totals.find('.-dir'),

      $disk = $container.find('.-disk'),
      $diskFree = $disk.find('.-free'),
      $diskSize = $disk.find('.-size'),

      $moreInfo = $container.find('.-more-info');

    var beforeData = function () {
        if (!$stats.hasClass('hover')) {
          plugin.statsRunning = false;
          return; // Hidden now.
        }
        $refreshing.show();

        $chartA.hide(); // Hide.
        $chartB.hide(); // Hide.

        $totals.removeClass('-no-charts');
        $totals.css('visibility', 'hidden');
        if (!canSeeMore || $.trim($totalDir.text()).length > 30) {
          $totalDir.hide(); // Hide this.
        }
        $disk.css('visibility', 'hidden');

        if (canSeeMore) { // Will display?
          $moreInfo.css('visibility', 'hidden');
        } else { // Not showing.
          $moreInfo.hide();
        }
        if (!plugin.statsData) {
          var postVars = {
            _wpnonce: plugin.vars._wpnonce
          }; // HTTP post vars.
          postVars[plugin.namespace] = {
            ajaxDirStats: '1'
          };
          $.post(plugin.vars.ajaxURL, postVars, function (data) {
            console.log('Admin Bar :: statsData :: %o', data);
            plugin.statsData = data;
            afterData();
          });
        } else {
          setTimeout(afterData, 500);
        }
      },
      afterData = function () {
        if (!plugin.statsData) {
          plugin.statsRunning = false;
          return; // Not possible.
        }
        if (!$stats.hasClass('hover')) {
          plugin.statsRunning = false;
          return; // Hidden now.
        }
        $refreshing.hide();

        $chartA.css('display', 'block');
        $chartB.css('display', 'block');

        var chartA = null, // Initialize.
          chartB = null, // Initialize.
          chartADimensions = null, // Initialize.
          chartBDimensions = null; // Initialize.

        var forCache = canSeeMore ? 'forCache' : 'forHostCache',
          forHtmlCCache = canSeeMore ? 'forHtmlCCache' : 'forHtmlCHostCache',
          largestCacheSize = canSeeMore ? 'largestCacheSize' : 'largestHostCacheSize',
          largestCacheCount = canSeeMore ? 'largestCacheCount' : 'largestHostCacheCount';

        var largestSize = plugin.statsData[largestCacheSize].size,
          largestSizeInDays = plugin.statsData[largestCacheSize].days,

          largestCount = plugin.statsData[largestCacheCount].count,
          largestCountInDays = plugin.statsData[largestCacheCount].days,

          forCache_totalLinksFiles = plugin.statsData[forCache].stats.total_links_files,
          forHtmlCCache_totalLinksFiles = plugin.statsData[forHtmlCCache].stats.total_links_files,
          totalLinksFiles = forCache_totalLinksFiles + forHtmlCCache_totalLinksFiles,

          forCache_totalSize = plugin.statsData[forCache].stats.total_size,
          forHtmlCCache_totalSize = plugin.statsData[forHtmlCCache].stats.total_size,
          totalSize = forCache_totalSize + forHtmlCCache_totalSize,

          forCache_diskSize = plugin.statsData[forCache].stats.disk_total_space,
          forCache_diskFree = plugin.statsData[forCache].stats.disk_free_space,

          forHostCache_totalLinksFiles = 0,
          forHtmlCHostCache_totalLinksFiles = 0,
          hostTotalLinksFiles = 0,
          forHostCache_totalSize = 0,
          forHtmlCHostCache_totalSize = 0,
          hostTotalSize = 0; // Initializing only, for now.

        if (plugin.vars.isMultisite && plugin.vars.currentUserHasNetworkCap) {
          forHostCache_totalLinksFiles = plugin.statsData.forHostCache.stats.total_links_files;
          forHtmlCHostCache_totalLinksFiles = plugin.statsData.forHtmlCHostCache.stats.total_links_files;
          hostTotalLinksFiles = forHostCache_totalLinksFiles + forHtmlCHostCache_totalLinksFiles;

          forHostCache_totalSize = plugin.statsData.forHostCache.stats.total_size;
          forHtmlCHostCache_totalSize = plugin.statsData.forHtmlCHostCache.stats.total_size;
          hostTotalSize = forHostCache_totalSize + forHtmlCHostCache_totalSize;
        }
        var chartScale = function (data, steps) {
            if (!(data instanceof Array)) {
              return {}; // Not possible.
            }
            if (typeof steps !== 'number' || steps <= 0) {
              steps = 10; // Default number of steps.
            }
            var values = []; // Initialize.
            $.each(data, function (index, payload) {
              values.push(Number(payload.value));
            });
            var start = 0, // Always zero.
              min = Math.min.apply(null, values),
              max = Math.max.apply(null, values),
              stepWidth = Math.ceil((max - start) / steps);

            return {
              scaleSteps: steps,
              scaleStartValue: start,
              scaleStepWidth: stepWidth,
              scaleIntegersOnly: true,
              scaleOverride: true
            };
          },
          chartAOptions = {
            responsive: true,
            maintainAspectRatio: true,

            animationSteps: 35,

            scaleFontSize: 10,
            scaleShowLine: true,
            scaleFontFamily: 'sans-serif',
            scaleShowLabelBackdrop: true,
            scaleBackdropPaddingY: 2,
            scaleBackdropPaddingX: 4,
            scaleFontColor: 'rgba(0,0,0,1)',
            scaleBackdropColor: 'rgba(255,255,255,1)',
            scaleLineColor: $('body').hasClass('admin-color-light') ? 'rgba(0,0,0,0.25)' : 'rgba(255,255,255,0.25)',
            scaleLabel: function (payload) {
              return plugin.bytesToSizeLabel(Number(payload.value));
            },
            tooltipFontSize: 12,
            tooltipFillColor: 'rgba(0,0,0,1)',
            tooltipFontFamily: 'Georgia, serif',
            tooltipTemplate: function (payload) {
              return payload.label + ': ' + plugin.bytesToSizeLabel(Number(payload.value));
            },
            segmentShowStroke: true,
            segmentStrokeWidth: 1,
            segmentStrokeColor: $('body').hasClass('admin-color-light') ? 'rgba(0,0,0,1)' : 'rgba(255,255,255,1)'
          }, // ↑ Merged w/ global config. options.

          chartBOptions = chartAOptions;

        var chartAData = [],
          chartBData = [];

        chartAData.push({
          value: largestSize,
          label: plugin.vars.i18n.xDayHigh
            .replace('%s', largestSizeInDays),
          color: '#ff5050',
          highlight: '#c63f3f'
        });
        chartAData.push({
          value: totalSize,
          label: plugin.vars.i18n.currentTotal,
          color: '#46bf52',
          highlight: '#33953e'
        });
        chartAData.push({
          value: forCache_totalSize,
          label: plugin.vars.i18n.pageCache,
          color: '#0096CC',
          highlight: '#057ca7'
        });
        chartAData.push({
          value: forHtmlCCache_totalSize,
          label: plugin.vars.i18n.htmlCompressor,
          color: '#FFC870',
          highlight: '#d6a85d'
        });
        if (plugin.vars.isMultisite && plugin.vars.currentUserHasNetworkCap) {
          chartAData.push({
            value: hostTotalSize,
            label: plugin.vars.i18n.currentSite,
            color: '#46bfb4',
            highlight: '#348f87'
          });
        }
        $.extend(chartAOptions, chartScale(chartAData, 5));

        chartBData = chartAData; // Same for now.
        $.extend(chartBOptions, chartScale(chartBData, 5));

        if ((chartA = $stats.data('chartA'))) {
          chartA.destroy(); // Destroy previous.
        }
        if ((chartB = $stats.data('chartB'))) {
          chartB.destroy(); // Destroy previous.
        }
        if ((chartADimensions = $stats.data('chartADimensions'))) {
          $chartA.attr('width', parseInt(chartADimensions.width))
            .attr('height', parseInt(chartADimensions.height))
            .css(chartADimensions); // Restore.
        }
        if ((chartBDimensions = $stats.data('chartBDimensions'))) {
          $chartB.attr('width', parseInt(chartBDimensions.width))
            .attr('height', parseInt(chartBDimensions.height))
            .css(chartBDimensions); // Restore.
        }
        if ($chartA.length && chartAData[0].value > 0) {
          chartA = new Chart($chartA[0].getContext('2d')).PolarArea(chartAData, chartAOptions);
          $stats.data('chartA', chartA).data('chartADimensions', {
            width: $chartA.width() + 'px',
            height: $chartA.height() + 'px'
          });
        } else {
          chartA = null; // Nullify.
          $chartA.hide(); // Hide if not showing.
        }
        if ($chartB.length && chartBData[0].value > 0) {
          chartB = new Chart($chartB[0].getContext('2d')).PolarArea(chartBData, chartBOptions);
          $stats.data('chartB', chartB).data('chartBDimensions', {
            width: $chartB.width() + 'px',
            height: $chartB.height() + 'px'
          });
        } else {
          chartB = null; // Nullify.
          $chartB.hide(); // Hide if not showing.
        }
        if (!chartA && !chartB) {
          $totals.addClass('-no-charts');
        }
        $totals.css('visibility', 'visible'); // Make this visible now.
        $totalFiles.find('.-value').html(plugin.escHtml(plugin.numberFormat(totalLinksFiles) + ' ' + (totalLinksFiles === 1 ? plugin.vars.i18n.file : plugin.vars.i18n.files)));
        $totalSize.find('.-value').html(plugin.escHtml(plugin.bytesToSizeLabel(totalSize)));

        $disk.css('visibility', 'visible'); // Make this visible now also.
        $diskSize.find('.-value').html(plugin.escHtml(plugin.bytesToSizeLabel(forCache_diskSize)));
        $diskFree.find('.-value').html(plugin.escHtml(plugin.bytesToSizeLabel(forCache_diskFree)));

        if (canSeeMore) { // Will display this?
          $moreInfo.css('visibility', 'visible');
        }
        plugin.statsRunning = false;
      };
    beforeData(); // Begin w/ data acquisition.
  };


  plugin.bytesToSizeLabel = function (bytes, decimals) {
    if (typeof bytes !== 'number' || bytes <= 1) {
      return bytes === 1 ? '1 byte' : '0 bytes';
    } // See: <http://jas.xyz/1gOCXob>
    if (typeof decimals !== 'number' || decimals <= 0) {
      decimals = 0; // Default; integer.
    }
    var base = 1024, // 1 Kilobyte base (binary).
      baseLog = Math.floor(Math.log(bytes) / Math.log(base)),
      sizes = ['bytes', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'],
      sizeInBaseLog = (bytes / Math.pow(base, baseLog));

    return sizeInBaseLog.toFixed(decimals) + ' ' + sizes[baseLog];
  };

  plugin.numberFormat = function (number, decimals) {
    if (typeof number !== 'number') {
      return String(number);
    } // See: <http://jas.xyz/1JlFD9P>
    if (typeof decimals !== 'number' || decimals <= 0) {
      decimals = 0; // Default; integer.
    }
    return number.toFixed(decimals).replace(/./g, function (m, o, s) {
      return o && m !== '.' && ((s.length - o) % 3 === 0) ? ',' + m : m;
    });
  };

  plugin.escHtml = function (string) {
    var entityMap = {
      '&': '&amp;',
      '<': '&lt;',
      '>': '&gt;',
      '"': '&quot;',
      "'": '&#39;'
    };
    return String(string).replace(/[&<>"']/g, function (specialChar) {
      return entityMap[specialChar];
    });
  };

  plugin.preventDefault = function (event, stop) {
    if (!event) {
      return; // Not possible.
    }
    event.preventDefault(); // Always.

    if (stop) {
      event.stopImmediatePropagation();
    }
  };

  plugin.MutationObserver = (function () {
    var observer = null; // Initialize default value.
    $.each(['', 'WebKit', 'O', 'Moz', 'Ms'], function (index, prefix) {
      if (prefix + 'MutationObserver' in window) {
        observer = window[prefix + 'MutationObserver'];
        return false; // Stop iterating now.
      } // See: <http://jas.xyz/1JlzCdi>
    });
    return observer; // See: <http://caniuse.com/#feat=mutationobserver>
  }());
  /*[/pro]*/
  plugin.animationEndEvents = // All vendor prefixes.
    'webkitAnimationEnd mozAnimationEnd msAnimationEnd oAnimationEnd animationEnd';

  $document.ready(plugin.onReady); // On DOM ready.

})(jQuery);
