(function ($) {
  'use strict'; // Standards.

  var plugin = {
      namespace: 'zencache'
    },
    $window = $(window),
    $document = $(document);

  plugin.onReady = function () {
    /*![pro strip-from='lite']*/
    plugin.dirStatsData = null;
    plugin.dirStatsRunning = false;
    /*![/pro]*/
    plugin.$menuPage = $('#plugin-menu-page');
    plugin.vars = window[plugin.namespace + '_menu_page_vars'];

    $('.plugin-menu-page-panel-heading', plugin.$menuPage).on('click', plugin.togglePanel);
    $('.plugin-menu-page-panels-open', plugin.$menuPage).on('click', plugin.toggleAllPanelsOpen);
    $('.plugin-menu-page-panels-close', plugin.$menuPage).on('click', plugin.toggleAllPanelsClose);

    $('[data-action]', plugin.$menuPage).on('click', plugin.doDataAction);
    $('[data-toggle-target]', plugin.$menuPage).on('click', plugin.doDataToggleTarget);

    $('select[name$="_enable\\]"], select[name$="_enable_flavor\\]"]', plugin.$menuPage).not('.no-if-enabled').on('change', plugin.enableDisable).trigger('change');
    $('textarea[name$="\[cdn_hosts\]"]', plugin.$menuPage).on('input propertychange', plugin.handleCdnHostsChange);

    /*![pro strip-from='lite']*/
    if ($('.plugin-menu-page-dir-stats', plugin.$menuPage).length) {
      $('.plugin-menu-page-dir-stats-button').on('click', plugin.dirStats);
      plugin.dirStats(); // Display directory stats.
    }
    /*![/pro]*/
  };

  plugin.toggleAllPanelsOpen = function (event) {
    plugin.preventDefault(event);

    $('.plugin-menu-page-panel-heading', plugin.$menuPage).addClass('open')
      .next('.plugin-menu-page-panel-body').addClass('open');
  };

  plugin.toggleAllPanelsClose = function (event) {
    plugin.preventDefault(event);

    $('.plugin-menu-page-panel-heading', plugin.$menuPage).removeClass('open')
      .next('.plugin-menu-page-panel-body').removeClass('open');
  };

  plugin.togglePanel = function (event) {
    plugin.preventDefault(event);

    $(this).toggleClass('open') // Heading and body.
      .next('.plugin-menu-page-panel-body').toggleClass('open');
  };

  plugin.doDataAction = function (event) {
    plugin.preventDefault(event);

    var $this = $(this),
      data = $this.data();
    if (typeof data.confirmation !== 'string' || confirm(data.confirmation))
      location.href = data.action;
  };

  plugin.enableDisable = function (event) {
    var $this = $(this),
      thisValue = $this.val(),
      thisName = $this.attr('name'),
      $thisPanel = $this.closest('.plugin-menu-page-panel');

    if ((thisName.indexOf('_enable]') !== -1 && (thisValue === '' || thisValue === '1')) || (thisName.indexOf('_flavor]') !== -1 && thisValue !== '0'))
      $thisPanel.find('.plugin-menu-page-panel-if-enabled').css('opacity', 1).find(':input').removeAttr('readonly');
    else $thisPanel.find('.plugin-menu-page-panel-if-enabled').css('opacity', 0.4).find(':input').attr('readonly', 'readonly');
  };

  plugin.doDataToggleTarget = function (event) {
    plugin.preventDefault(event);

    var $this = $(this),
      $target = $($this.data('toggleTarget'));

    if ($target.is(':visible')) {
      $target.hide();
      $this.find('.si')
        .removeClass('si-eye-slash')
        .addClass('si-eye');
    } else {
      $target.show();
      $this.find('.si')
        .removeClass('si-eye')
        .addClass('si-eye-slash');
    }
  };

  plugin.handleCdnHostsChange = function (event) {
    var $cdnHosts = $(this),
      $cdnHost = $('input[name$="\[cdn_host\]"]', plugin.$menuPage);

    if ($.trim($cdnHosts.val())) {
      if ($cdnHost.val()) {
        $cdnHost.data('hiddenValue', $cdnHost.val());
      }
      $cdnHost.attr('disabled', 'disabled').val('');
    } else {
      if (!$cdnHost.val()) {
        $cdnHost.val($cdnHost.data('hiddenValue'));
      }
      $cdnHost.removeAttr('disabled');
      $cdnHosts.val('');
    }
  };

  /*![pro strip-from='lite']*/
  plugin.dirStats = function (event) {
    plugin.preventDefault(event);

    if (plugin.dirStatsRunning) {
      return; // Still running.
    }
    plugin.dirStatsRunning = true;

    var canSeeMore = !plugin.vars.isMultisite || plugin.vars.currentUserHasNetworkCap;

    var $body = $('body'), // Needed below.

      $stats = $('.plugin-menu-page-dir-stats', plugin.$menuPage),

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
      $diskSize = $disk.find('.-size');

    var beforeData = function () {
        $refreshing.show();

        $chartA.hide(); // Hide.
        $chartB.hide(); // Hide.

        $totals.css('visibility', 'hidden');
        $disk.css('visibility', 'hidden');

        if (!canSeeMore || $.trim($totalDir.text()).length > 30) {
          $totalDir.hide(); // Hide this.
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
            maintainAspectRatio: true,

            animationSteps: 35,

            scaleFontSize: 14,
            scaleShowLine: true,
            scaleBeginAtZero: true,
            scaleIntegersOnly: true,
            scaleFontFamily: 'sans-serif',
            scaleShowLabelBackdrop: true,
            scaleBackdropPaddingY: 2,
            scaleBackdropPaddingX: 4,
            scaleFontColor: 'rgba(0,0,0,1)',
            scaleBackdropColor: 'rgba(255,255,255,1)',
            scaleLineColor: 'rgba(0,0,0,0.15)',
            scaleLabel: function (payload) {
              return plugin.numberFormat(Number(payload.value)) +
                ' ' + (payload.value === 1 ? plugin.vars.i18n.file : plugin.vars.i18n.files);
            },
            tooltipFontSize: 18,
            tooltipFillColor: 'rgba(0,0,0,1)',
            tooltipFontFamily: 'Georgia, serif',
            tooltipTemplate: function (payload) {
              return payload.label + ': ' + plugin.numberFormat(Number(payload.value)) +
                ' ' + (payload.value === 1 ? plugin.vars.i18n.file : plugin.vars.i18n.files);
            },
            segmentShowStroke: true,
            segmentStrokeWidth: 2,
            segmentStrokeColor: 'rgba(255,255,255,1)'
          }, // ↑ Merged w/ global config. options.

          chartBOptions = $.extend({}, chartAOptions, {
            scaleLabel: function (payload) {
              return plugin.bytesToSizeLabel(Number(payload.value));
            },
            tooltipTemplate: function (payload) {
              return payload.label + ': ' + plugin.bytesToSizeLabel(Number(payload.value));
            },
          });

        var chartAData = [],
          chartBData = [];

        chartAData.push({
          value: largestCount,
          label: plugin.vars.i18n.xDayHigh
            .replace('%s', largestCountInDays),
          color: '#ff5050',
          highlight: '#c63f3f'
        });
        chartAData.push({
          value: totalLinksFiles,
          label: plugin.vars.i18n.currentTotal,
          color: '#46bf52',
          highlight: '#33953e'
        });
        chartAData.push({
          value: forCache_totalLinksFiles,
          label: plugin.vars.i18n.pageCache,
          color: '#0096CC',
          highlight: '#057ca7'
        });
        chartAData.push({
          value: forHtmlCCache_totalLinksFiles,
          label: plugin.vars.i18n.htmlCompressor,
          color: '#FFC870',
          highlight: '#d6a85d'
        });
        if (plugin.vars.isMultisite && plugin.vars.currentUserHasNetworkCap) {
          chartAData.push({
            value: hostTotalLinksFiles,
            label: plugin.vars.i18n.currentSite,
            color: '#46bfb4',
            highlight: '#348f87'
          });
        }
        chartBData.push({
          value: largestSize,
          label: plugin.vars.i18n.xDayHigh
            .replace('%s', largestSizeInDays),
          color: '#ff5050',
          highlight: '#c63f3f'
        });
        chartBData.push({
          value: totalSize,
          label: plugin.vars.i18n.currentTotal,
          color: '#46bf52',
          highlight: '#33953e'
        });
        chartBData.push({
          value: forCache_totalSize,
          label: plugin.vars.i18n.pageCache,
          color: '#0096CC',
          highlight: '#057ca7'
        });
        chartBData.push({
          value: forHtmlCCache_totalSize,
          label: plugin.vars.i18n.htmlCompressor,
          color: '#FFC870',
          highlight: '#d6a85d'
        });
        if (plugin.vars.isMultisite && plugin.vars.currentUserHasNetworkCap) {
          chartBData.push({
            value: hostTotalSize,
            label: plugin.vars.i18n.currentSite,
            color: '#46bfb4',
            highlight: '#348f87'
          });
        }
        if ((chartA = $stats.data('chartA'))) {
          chartA.destroy(); // Destroy previous.
        }
        if ((chartB = $stats.data('chartB'))) {
          chartB.destroy(); // Destroy previous.
        }
        if ((chartADimensions = $stats.data('chartADimensions'))) {
          $chartA.find('.-canvas')
            .attr('width', parseInt(chartADimensions.width))
            .attr('height', parseInt(chartADimensions.height))
            .css(chartADimensions); // Restore.
        }
        if ((chartBDimensions = $stats.data('chartBDimensions'))) {
          $chartB.find('.-canvas')
            .attr('width', parseInt(chartBDimensions.width))
            .attr('height', parseInt(chartBDimensions.height))
            .css(chartBDimensions); // Restore.
        }
        if ($chartA.length && chartAData[0].value > 0) {
          chartA = new Chart($chartA.find('.-canvas')[0].getContext('2d')).PolarArea(chartAData, chartAOptions);
          $stats.data('chartA', chartA).data('chartADimensions', {
            width: $chartA.find('.-canvas').width() + 'px',
            height: $chartA.find('.-canvas').height() + 'px'
          });
        } else {
          $chartA.hide(); // Hide if not showing.
        }
        if ($chartB.length && chartBData[0].value > 0) {
          chartB = new Chart($chartB.find('.-canvas')[0].getContext('2d')).PolarArea(chartBData, chartBOptions);
          $stats.data('chartB', chartB).data('chartBDimensions', {
            width: $chartB.find('.-canvas').width() + 'px',
            height: $chartB.find('.-canvas').height() + 'px'
          });
        } else {
          $chartB.hide(); // Hide if not showing.
        }
        $totals.css('visibility', 'visible'); // Make this visible now.
        $totalFiles.find('.-value').html(plugin.escHtml(plugin.numberFormat(totalLinksFiles) + ' ' + (totalLinksFiles === 1 ? plugin.vars.i18n.file : plugin.vars.i18n.files)));
        $totalSize.find('.-value').html(plugin.escHtml(plugin.bytesToSizeLabel(totalSize)));

        $disk.css('visibility', 'visible'); // Make this visible now also.
        $diskSize.find('.-value').html(plugin.escHtml(plugin.bytesToSizeLabel(forCache_diskSize)));
        $diskFree.find('.-value').html(plugin.escHtml(plugin.bytesToSizeLabel(forCache_diskFree)));

        plugin.dirStatsRunning = false;
      };
    beforeData(); // Begin w/ data acquisition.
  };
  /*![/pro]*/

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
  $document.ready(plugin.onReady); // On DOM ready.

})(jQuery);
