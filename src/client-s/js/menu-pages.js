(function ($) {
  'use strict'; // Standards.

  var plugin = {
      namespace: 'comet_cache'
    },
    $window = $(window),
    $document = $(document);

  plugin.onReady = function () {
    /*![pro strip-from='lite']*/
    plugin.statsData = null;
    plugin.statsRunning = false;
    /*![/pro]*/
    plugin.$menuPage = $('#plugin-menu-page');
    plugin.vars = window[plugin.namespace + '_menu_page_vars'];

    $('.plugin-menu-page-panel-heading', plugin.$menuPage).on('click', plugin.togglePanel);
    $('.plugin-menu-page-panels-open', plugin.$menuPage).on('click', plugin.toggleAllPanelsOpen);
    $('.plugin-menu-page-panels-close', plugin.$menuPage).on('click', plugin.toggleAllPanelsClose);

    $('[data-action]', plugin.$menuPage).on('click', plugin.doDataAction);
    $('[data-toggle-target]', plugin.$menuPage).on('click', plugin.doDataToggleTarget);

    $('select[name$="_enable\\]"]', plugin.$menuPage).not('.-no-if-enabled').on('change', plugin.enableDisable).trigger('change');

    /*![pro strip-from='lite']*/
    $('textarea[name$="\[cdn_hosts\]"]', plugin.$menuPage).on('input propertychange', plugin.handleCdnHostsChange);
    $('.plugin-menu-page-clear-cdn-cache', plugin.$menuPage).on('click', plugin.clearCdnCacheViaAjax);
    /*[/pro]*/

    /*![pro strip-from='lite']*/
    $('.-cache-clear-admin-bar-options select', plugin.$menuPage).on('change', plugin.handleCacheClearAdminBarOpsChange).trigger('change');
    /*[/pro]*/

    /*![pro strip-from='lite']*/
    if ($('.plugin-menu-page-stats', plugin.$menuPage).length) {
      $('.plugin-menu-page-stats-button').on('click', plugin.statsRefresh);
      plugin.stats(); // Display stats/charts.
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
      enabled = Number(thisValue) >= 1,

      $thisPanelBody = $this.closest('.plugin-menu-page-panel-body'),

      targetIfEnabled = $this.data('target'), // Optional specifier.
      $targetIfEnableds = targetIfEnabled ? $(targetIfEnabled, $thisPanelBody)
      .filter('.plugin-menu-page-panel-if-enabled') : null,

      $parentIfEnabled = $this.closest('.plugin-menu-page-panel-if-enabled'),
      $childIfEnableds = $parentIfEnabled.find('> .plugin-menu-page-panel-if-enabled'),

      $panelIfEnableds = $thisPanelBody.find('> .plugin-menu-page-panel-if-enabled');

    if (enabled) {
      if (targetIfEnabled) {
        $targetIfEnableds.css('opacity', 1).find(':input').removeAttr('readonly');
      } else if ($parentIfEnabled.length) {
        $childIfEnableds.css('opacity', 1).find(':input').removeAttr('readonly');
      } else {
        $panelIfEnableds.css('opacity', 1).find(':input').removeAttr('readonly');
      }
    } else {
      if (targetIfEnabled) {
        $targetIfEnableds.css('opacity', 0.4).find(':input').attr('readonly', 'readonly');
      } else if ($parentIfEnabled.length) {
        $childIfEnableds.css('opacity', 0.4).find(':input').attr('readonly', 'readonly');
      } else {
        $panelIfEnableds.css('opacity', 0.4).find(':input').attr('readonly', 'readonly');
      }
    }
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

  plugin.handleCacheClearAdminBarOpsChange = function (event) {
    var $select = $(this),
      val = $select.val(),
      $ss = $('.-clear-cache-ops-ss', plugin.$menuPage);
    $ss.attr('src', $ss.attr('src').replace(/ops[0-9]\-ss\.png$/, 'ops' + val + '-ss.png'));
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
  plugin.clearCdnCacheViaAjax = function (event) {
    plugin.preventDefault(event);

    var $this = $(this),
      postVars = {
        _wpnonce: plugin.vars._wpnonce
      }; // HTTP post vars.
    postVars[plugin.namespace] = {
      ajaxClearCdnCache: '1'
    };
    $this.attr('disabled', 'disabled'); // Processing state.

    $.post(plugin.vars.ajaxURL, postVars, function (response) {
      alert($(response.replace(/\<\/p\>\<p\>/gi, '</p> <p>')).text());
      $this.removeAttr('disabled');
    });
  };
  /*[/pro]*/

  /*![pro strip-from='lite']*/
  plugin.stats = function (event) {
    plugin.preventDefault(event);

    if (plugin.statsRunning) {
      return; // Still running.
    }
    plugin.statsRunning = true;

    var $body = $('body'), // Needed below.

      $stats = $('.plugin-menu-page-stats', plugin.$menuPage),

      $wrapper = $stats.find('.-wrapper'),
      $container = $wrapper.find('.-container'),

      $refreshing = $container.find('.-refreshing'),

      $totals = $container.find('.-totals'),
      $totalFiles = $totals.find('.-files'),
      $totalSize = $totals.find('.-size'),
      $totalDir = $totals.find('.-dir'),

      $disk = $container.find('.-disk'),
      $diskFree = $disk.find('.-free'),
      $diskSize = $disk.find('.-size'),

      $system = $container.find('.-system'),
      $sysMemoryUsage = $system.find('.-memory-usage'),
      $sysLoadAverage = $system.find('.-load-average'),

      $chartA = $container.find('.-chart-a'),
      $chartB = $container.find('.-chart-b'),

      $opcache = $container.find('.-opcache'),
      $opcacheMemory = $opcache.find('.-memory'),
      $opcacheTotals = $opcache.find('.-totals'),
      $opcacheHitsMisses = $opcache.find('.-hits-misses');

    var beforeData = function () {
        $refreshing.show();

        $totals.hide();
        if ($.trim($totalDir.text()).length > 30) {
          $totalDir.hide(); // Hide this.
        }
        $disk.hide();

        $system.hide();
        $sysMemoryUsage.hide();
        $sysLoadAverage.hide();

        $chartA.hide();
        $chartB.hide();

        $opcache.hide();

        if (!plugin.statsData) {
          var postVars = {
            _wpnonce: plugin.vars._wpnonce
          }; // HTTP post vars.
          postVars[plugin.namespace] = {
            ajaxStats: '1'
          };
          $.post(plugin.vars.ajaxURL, postVars, function (data) {
            console.log('Menu Page :: statsData :: %o', data);
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
        $refreshing.hide();

        $chartA.css('display', 'block');
        $chartB.css('display', 'block');

        var chartA = null, // Initialize.
          chartB = null, // Initialize.
          chartADimensions = null, // Initialize.
          chartBDimensions = null; // Initialize.

        var largestSize = plugin.statsData.largestCacheSize.size,
          largestSizeInDays = plugin.statsData.largestCacheSize.days,

          largestCount = plugin.statsData.largestCacheCount.count,
          largestCountInDays = plugin.statsData.largestCacheCount.days,

          forCache_totalLinksFiles = plugin.statsData.forCache.stats.total_links_files,
          forHtmlCCache_totalLinksFiles = plugin.statsData.forHtmlCCache.stats.total_links_files,
          totalLinksFiles = forCache_totalLinksFiles + forHtmlCCache_totalLinksFiles,

          forCache_totalSize = plugin.statsData.forCache.stats.total_size,
          forHtmlCCache_totalSize = plugin.statsData.forHtmlCCache.stats.total_size,
          totalSize = forCache_totalSize + forHtmlCCache_totalSize,

          forCache_diskSize = plugin.statsData.forCache.stats.disk_total_space,
          forCache_diskFree = plugin.statsData.forCache.stats.disk_free_space,

          sysLoadAverages = plugin.statsData.sysLoadAverages,
          sysMemoryStatus = plugin.statsData.sysMemoryStatus,
          sysOpcacheStatus = plugin.statsData.sysOpcacheStatus,

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
          chartAOptions = { // Chart.js config. options.
            responsive: true,
            maintainAspectRatio: true,

            animationSteps: 35,

            scaleFontSize: 14,
            scaleShowLine: true,
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
        $.extend(chartAOptions, chartScale(chartAData));

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
        $.extend(chartBOptions, chartScale(chartBData));

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
          $chartA.find('.-heading, .-canvas').show(), $chartA.find('.-empty').hide();
          chartA = new Chart($chartA.find('.-canvas')[0].getContext('2d')).PolarArea(chartAData, chartAOptions);
          $stats.data('chartA', chartA).data('chartADimensions', {
            width: $chartA.find('.-canvas').width() + 'px',
            height: $chartA.find('.-canvas').height() + 'px'
          });
        } else {
          $chartA.find('.-heading, .-canvas').hide(), $chartA.find('.-empty').show();
          $chartA.find('.-empty').html('<img style="width:100%;" src="' + plugin.escHtml(plugin.vars.emptyStatsCountsImageUrl) + '" />');
        }
        if ($chartB.length && chartBData[0].value > 0) {
          $chartB.find('.-heading, .-canvas').show(), $chartB.find('.-empty').hide();
          chartB = new Chart($chartB.find('.-canvas')[0].getContext('2d')).PolarArea(chartBData, chartBOptions);
          $stats.data('chartB', chartB).data('chartBDimensions', {
            width: $chartB.find('.-canvas').width() + 'px',
            height: $chartB.find('.-canvas').height() + 'px'
          });
        } else {
          $chartB.find('.-heading, .-canvas').hide(), $chartB.find('.-empty').show();
          $chartB.find('.-empty').html('<img style="width:100%;" src="' + plugin.escHtml(plugin.vars.emptyStatsFilesImageUrl) + '" />');
        }
        $totals.show(); // Give this a display value now.
        $totalFiles.find('.-value').html(plugin.escHtml(plugin.numberFormat(totalLinksFiles) + ' ' + (totalLinksFiles === 1 ? plugin.vars.i18n.file : plugin.vars.i18n.files)));
        $totalSize.find('.-value').html(plugin.escHtml(plugin.bytesToSizeLabel(totalSize)));

        $disk.show(); // Give this a display value now.
        $diskSize.find('.-value').html(plugin.escHtml(plugin.bytesToSizeLabel(forCache_diskSize)));
        $diskFree.find('.-value').html(plugin.escHtml(plugin.bytesToSizeLabel(forCache_diskFree)));

        if (sysMemoryStatus) {
          $system.show(); // Give this a display value now.
          $sysMemoryUsage.show().find('.-value').html(plugin.escHtml(sysMemoryStatus.percentage));
        }
        if (sysLoadAverages) {
          $system.show(); // Give this a display value now.
          $sysLoadAverage.show().find('.-value').html(plugin.escHtml(sysLoadAverages[0].toFixed(2)));
        }
        if (sysOpcacheStatus) {
          $opcache.show(); // Give this a display value now.

          $opcacheMemory.find('.-free .-value').html(plugin.bytesToSizeLabel(sysOpcacheStatus.memory_usage.free_memory));
          $opcacheMemory.find('.-used .-value').html(plugin.bytesToSizeLabel(sysOpcacheStatus.memory_usage.used_memory));
          $opcacheMemory.find('.-wasted .-value').html(plugin.bytesToSizeLabel(sysOpcacheStatus.memory_usage.wasted_memory));

          $opcacheTotals.find('.-scripts .-value').html(plugin.numberFormat(plugin.numberFormat(sysOpcacheStatus.opcache_statistics.num_cached_scripts)));
          $opcacheTotals.find('.-keys .-value').html(plugin.numberFormat(plugin.numberFormat(sysOpcacheStatus.opcache_statistics.num_cached_keys)));

          $opcacheHitsMisses.find('.-hits .-value').html(plugin.numberFormat(sysOpcacheStatus.opcache_statistics.hits));
          $opcacheHitsMisses.find('.-misses .-value').html(plugin.numberFormat(sysOpcacheStatus.opcache_statistics.misses));
          $opcacheHitsMisses.find('.-hit-rate .-value').html(sysOpcacheStatus.opcache_statistics.opcache_hit_rate.toFixed(2) + plugin.vars.i18n.perSymbol);
        }
        plugin.statsRunning = false;
      };
    beforeData(); // Begin w/ data acquisition.
  };

  plugin.statsRefresh = function (event) {
    plugin.preventDefault(event);
    plugin.statsData = null;
    plugin.stats();
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
