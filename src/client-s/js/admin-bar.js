/*![pro strip-from='lite']*/

(function ($) {
  'use strict'; // Standards.

  var plugin = {
      namespace: 'zencache'
    },
    $window = $(window),
    $document = $(document);

  plugin.onReady = function () {
    plugin.dirStatsData = null;
    plugin.dirStatsRunning = false;
    plugin.hideAJAXResponseTimeout = null;
    plugin.vars = $('#' + plugin.namespace + '-admin-bar-vars').data('json');

    $('#wp-admin-bar-' + plugin.namespace + '-wipe > a').on('click', plugin.wipeCache);
    $('#wp-admin-bar-' + plugin.namespace + '-clear > a').on('click', plugin.clearCache);
    $document.on('click', '.' + plugin.namespace + '-ajax-response', plugin.hideAJAXResponse);

    var $dirStats = $('#wp-admin-bar-' + plugin.namespace + '-dir-stats');
    if ($dirStats.length && plugin.MutationObserver) { // Possible?
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
            plugin.dirStats(); // Received `hover` class.
          }
          return false; // Stop iterating now.
        });
      }))
      .observe($dirStats[0], {
        attributes: true,
        childList: true,
        characterData: true
      }); // See: <http://jas.xyz/1JlzCdi>
      $dirStats.find('> a').on('click', plugin.preventDefault);
    }
  };

  plugin.wipeCache = function (event) {
    plugin.preventDefault(event);
    plugin.dirStatsData = null;

    var postVars = {
      _wpnonce: plugin.vars._wpnonce
    }; // HTTP post vars.
    postVars[plugin.namespace] = {
      ajaxWipeCache: '1'
    };
    var $wipe = $('#wp-admin-bar-' + plugin.namespace + '-wipe > a');

    plugin.removeAJAXResponse();
    $wipe.parent().addClass('-processing');
    $wipe.attr('disabled', 'disabled');

    $.post(plugin.vars.ajaxURL, postVars, function (data) {
      plugin.removeAJAXResponse();
      $wipe.parent().removeClass('-processing');
      $wipe.removeAttr('disabled');

      var $response = $('<div class="' + plugin.namespace + '-ajax-response -wipe">' + data + '</div>');
      $('body').append($response); // Append response.
      plugin.showAJAXResponse(); // Show response.
    });
  };

  plugin.clearCache = function (event) {
    plugin.preventDefault(event);
    plugin.dirStatsData = null;

    var postVars = {
      _wpnonce: plugin.vars._wpnonce
    }; // HTTP post vars.
    postVars[plugin.namespace] = {
      ajaxClearCache: '1'
    };
    var $clear = $('#wp-admin-bar-' + plugin.namespace + '-clear > a');

    plugin.removeAJAXResponse();
    $clear.parent().addClass('-processing');
    $clear.attr('disabled', 'disabled');

    $.post(plugin.vars.ajaxURL, postVars, function (data) {
      plugin.removeAJAXResponse();
      $clear.parent().removeClass('-processing');
      $clear.removeAttr('disabled');

      var $response = $('<div class="' + plugin.namespace + '-ajax-response -clear">' + data + '</div>');
      $('body').append($response); // Append response.
      plugin.showAJAXResponse(); // Show response.
    });
  };

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

  plugin.dirStats = function () {
    if (plugin.dirStatsRunning) {
      return; // Still running.
    }
    plugin.dirStatsRunning = true;

    var canSeeMore = !plugin.vars.isMultisite || plugin.vars.currentUserHasNetworkCap;

    var $body = $('body'), // Needed below.

      $stats = $('#wp-admin-bar-' + plugin.namespace + '-dir-stats'),

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
          plugin.dirStatsRunning = false;
          return; // Hidden now.
        }
        $refreshing.show();

        $chartA.hide(); // Hide.
        $chartB.hide(); // Hide.

        $totals.css('visibility', 'hidden');
        $disk.css('visibility', 'hidden');

        if (canSeeMore) { // Will display?
          $moreInfo.css('visibility', 'hidden');
        } else { // Not showing.
          $totalDir.hide();
          $moreInfo.hide();
        }
        if (!plugin.dirStatsData) {
          var postVars = {
            _wpnonce: plugin.vars._wpnonce
          }; // HTTP post vars.
          postVars[plugin.namespace] = {
            ajaxDirStats: '1'
          };
          $.post(plugin.vars.ajaxURL, postVars, function (data) {
            plugin.dirStatsData = data;
            afterData();
          });
        } else {
          setTimeout(afterData, 500);
        }
      },
      afterData = function () {
        if (!plugin.dirStatsData) {
          plugin.dirStatsRunning = false;
          return; // Not possible.
        }
        if (!$stats.hasClass('hover')) {
          plugin.dirStatsRunning = false;
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

        var largestSize = plugin.dirStatsData[largestCacheSize].size,
          largestSizeInDays = plugin.dirStatsData[largestCacheSize].days,

          largestCount = plugin.dirStatsData[largestCacheCount].count,
          largestCountInDays = plugin.dirStatsData[largestCacheCount].days,

          forCache_totalLinksFiles = plugin.dirStatsData[forCache].stats.total_links_files,
          forHtmlCCache_totalLinksFiles = plugin.dirStatsData[forHtmlCCache].stats.total_links_files,
          totalLinksFiles = forCache_totalLinksFiles + forHtmlCCache_totalLinksFiles,

          forCache_totalSize = plugin.dirStatsData[forCache].stats.total_size,
          forHtmlCCache_totalSize = plugin.dirStatsData[forHtmlCCache].stats.total_size,
          totalSize = forCache_totalSize + forHtmlCCache_totalSize,

          forCache_diskSize = plugin.dirStatsData[forCache].stats.disk_total_space,
          forCache_diskFree = plugin.dirStatsData[forCache].stats.disk_free_space,

          forHostCache_totalLinksFiles = 0,
          forHtmlCHostCache_totalLinksFiles = 0,
          hostTotalLinksFiles = 0,
          forHostCache_totalSize = 0,
          forHtmlCHostCache_totalSize = 0,
          hostTotalSize = 0; // Initializing only, for now.

        if (plugin.vars.isMultisite && plugin.vars.currentUserHasNetworkCap) {
          forHostCache_totalLinksFiles = plugin.dirStatsData.forHostCache.stats.total_links_files;
          forHtmlCHostCache_totalLinksFiles = plugin.dirStatsData.forHtmlCHostCache.stats.total_links_files;
          hostTotalLinksFiles = forHostCache_totalLinksFiles + forHtmlCHostCache_totalLinksFiles;

          forHostCache_totalSize = plugin.dirStatsData.forHostCache.stats.total_size;
          forHtmlCHostCache_totalSize = plugin.dirStatsData.forHtmlCHostCache.stats.total_size;
          hostTotalSize = forHostCache_totalSize + forHtmlCHostCache_totalSize;
        }
        var chartAOptions = { // Chart.js config. options.
            responsive: true,
            animationSteps: 35,

            scaleFontSize: 10,
            scaleShowLine: true,
            scaleBeginAtZero: true,
            scaleFontFamily: 'sans-serif',
            scaleShowLabelBackdrop: true,
            scaleBackdropPaddingY: 2,
            scaleBackdropPaddingX: 4,
            scaleFontColor: 'rgba(0,0,0,1)',
            scaleBackdropColor: 'rgba(255,255,255,1)',
            scaleLineColor: $('body').hasClass('admin-color-light') ?
              'rgba(0,0,0,0.25)' : 'rgba(255,255,255,0.25)',
            scaleLabel: function (payload) {
              return plugin.bytesToSizeLabel(payload.value);
            },

            tooltipFontSize: 12,
            tooltipFillColor: 'rgba(0,0,0,1)',
            tooltipFontFamily: 'Georgia, serif',
            tooltipTemplate: function (payload) {
              return payload.label + ': ' + plugin.bytesToSizeLabel(payload.value);
            },

            segmentShowStroke: true,
            segmentStrokeWidth: 1,
            segmentStrokeColor: $('body').hasClass('admin-color-light') ?
              'rgba(0,0,0,1)' : 'rgba(255,255,255,1)'
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
        chartBData = chartAData;

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
        if ($chartA.length) {
          chartA = new Chart($chartA[0].getContext('2d')).PolarArea(chartAData, chartAOptions);
          $stats.data('chartA', chartA).data('chartADimensions', {
            width: $chartA.width() + 'px',
            height: $chartA.height() + 'px'
          });
        }
        if ($chartB.length) {
          chartB = new Chart($chartB[0].getContext('2d')).PolarArea(chartBData, chartBOptions);
          $stats.data('chartB', chartB).data('chartBDimensions', {
            width: $chartB.width() + 'px',
            height: $chartB.height() + 'px'
          });
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
        plugin.dirStatsRunning = false;
      };
    beforeData(); // Begin w/ data acquisition.
  };

  plugin.bytesToSizeLabel = function (bytes, decimals) {
    if (isNaN(bytes) || bytes <= 1) {
      return bytes === 1 ? '1 byte' : '0 bytes';
    } // See: <http://jas.xyz/1gOCXob>
    if (isNaN(decimals) || decimals <= 0) {
      decimals = 0; // Default; integer.
    }
    var base = 1024, // 1 Kilobyte base (binary).
      baseLog = Math.floor(Math.log(bytes) / Math.log(base)),
      sizes = ['bytes', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'],
      sizeInBaseLog = (bytes / Math.pow(base, baseLog));

    return sizeInBaseLog.toFixed(decimals) + ' ' + sizes[baseLog];
  };

  plugin.numberFormat = function (number, decimals) {
    if (isNaN(number)) {
      return String(number);
    } // See: <http://jas.xyz/1JlFD9P>
    if (isNaN(decimals) || decimals <= 0) {
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

  plugin.animationEndEvents = // All vendor prefixes.
    'webkitAnimationEnd mozAnimationEnd msAnimationEnd oAnimationEnd animationEnd';

  $document.ready(plugin.onReady); // On DOM ready.

})(jQuery);

/*![/pro]*/
