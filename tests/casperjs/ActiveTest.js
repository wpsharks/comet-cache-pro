$.test.begin('Check if plugin is active.', function () {
  $.start();
  $$.wp.thenLogin();

  if ($$.wp.isMultisite()) {
    $.thenOpen($$.www.url('/wp-admin/network/admin.php?page=' + $$$.GLOBAL_NS));
  } else {
    $.echo($$.www.url('/wp-admin/admin.php?page=' + $$$.GLOBAL_NS));
    $.thenOpen($$.www.url('/wp-admin/admin.php?page=' + $$$.GLOBAL_NS));
  }
  $.then(function () {
    $.echo($.getHTML());
    $.test.assertExists('input[type="radio"][name="' + $$$.GLOBAL_NS + '[saveOptions][enable]"][value="1"]:checked');
  });
  $$.wp.thenLogout();

  $.run(function () {
    $.test.done();
  });
});
