<?php

// This is a PLUGIN TEMPLATE.

// Copy this file to a new name like abc_myplugin.php.  Edit the code, then
// run this file at the command line to produce a plugin for distribution:
// $ php abc_myplugin.php > abc_myplugin-0.1.txt

// Plugin name is optional.  If unset, it will be extracted from the current
// file name. Plugin names should start with a three letter prefix which is
// unique and reserved for each plugin author ("abc" is just an example).
// Uncomment and edit this line to override:
$plugin['name'] = 'goe_hardcoded';

// Allow raw HTML help, as opposed to Textile.
// 0 = Plugin help is in Textile format, no raw HTML allowed (default).
// 1 = Plugin help is in raw HTML.  Not recommended.
# $plugin['allow_html_help'] = 1;

$plugin['version'] = '0.1';
$plugin['author'] = 'Georg Oechsler';
$plugin['author_uri'] = 'http://txp.oechsler.de';
$plugin['description'] = 'Manage pages, forms and styles with files.';

// Plugin load order:
// The default value of 5 would fit most plugins, while for instance comment
// spam evaluators or URL redirectors would probably want to run earlier
// (1...4) to prepare the environment for everything else that follows.
// Values 6...9 should be considered for plugins which would work late.
// This order is user-overrideable.
$plugin['order'] = '5';

// Plugin 'type' defines where the plugin is loaded
// 0 = public       : only on the public side of the website (default)
// 1 = public+admin : on both the public and admin side
// 2 = library      : only when include_plugin() or require_plugin() is called
// 3 = admin        : only on the admin side
$plugin['type'] = '3';

// Plugin "flags" signal the presence of optional capabilities to the core plugin loader.
// Use an appropriately OR-ed combination of these flags.
// The four high-order bits 0xf000 are available for this plugin's private use
if (!defined('PLUGIN_HAS_PREFS')) define('PLUGIN_HAS_PREFS', 0x0001); // This plugin wants to receive "plugin_prefs.{$plugin['name']}" events
if (!defined('PLUGIN_LIFECYCLE_NOTIFY')) define('PLUGIN_LIFECYCLE_NOTIFY', 0x0002); // This plugin wants to receive "plugin_lifecycle.{$plugin['name']}" events

$plugin['flags'] = '0';

if (!defined('txpinterface'))
        @include_once('zem_tpl.php');

# --- BEGIN PLUGIN CODE ---
if (txpinterface == 'admin') {
  add_privs('goe_hardcoded');
  register_tab('extensions', 'goe_hardcoded', gTxt('Hardcoded'));
  register_callback('goe_hardcoded', 'goe_hardcoded');

  define('GOE_HARDCODED_NOT_READABLE', "no file");
  define('GOE_HARDCODED_NOT_WRITEABLE', "not readable");
  define('GOE_HARDCODED_FILE_DIFFERS', "file differs");
  define('GOE_HARDCODED_FILE_EXISTS', "file exists");
}

function goe_hardcoded($event, $step) {
  pagetop("Hardcoded");

  switch($step) {
    case "to_db":
      goe_hardcoded_action_to_db(gps("class"), gps("name"), gps("type"));
      //header("Location: index.php?event=goe_hardcoded");
    break;
    case "to_file":
      goe_hardcoded_action_to_file(gps("class"), gps("name"), gps("type"));
      header("Location: index.php?event=goe_hardcoded");
    break;
    case "diff":
      goe_hardcoded_action_diff(gps("class"), gps("name"), gps("type"));
      return;
    break;
    case "delete":
      goe_hardcoded_action_delete(gps("class"), gps("name"), gps("type"));
      header("Location: index.php?event=goe_hardcoded");
    break;
  } 

  $table = startTable("hardcoded") . tr(td() . td('Name') . td('Status') . tda('Actions', ' colspan="4"'));

  // Pages
  $resultset = safe_rows_start("name, md5(user_html) as hash", "txp_page", "1=1 order by name");
  $table .= tr(td() . tda(hed('Pages', 2), ' colspan="2"'));
  while ($row = nextRow($resultset)) {
    $table .= tr(   
      td()
      . td($row['name'])
      . td(goe_hardcoded_status('page', $row['name'], $row['hash'], $row['type']))
      . goe_hardcoded_actions('page', $row['name'], $row['hash'], $row['type'])
      . "\n"
    );
    $last_type = $row['type'];
  }

  // Forms
  $resultset = safe_rows_start("type, name, md5(form) as hash", "txp_form", "1=1 order by type, name");
  $table .= tr(td() . tda(hed('Forms', 2), ' colspan="2"'));
  $last_type = "";
  while ($row = nextRow($resultset)) {
    $table .= tr(   
      td(($last_type !== $row['type']) ? $row['type'] . '' : '')
      . td($row['name'])
      . td(goe_hardcoded_status('form', $row['name'], $row['hash'], $row['type']))
      . goe_hardcoded_actions('form', $row['name'], $row['hash'], $row['type'])
      . "\n"
    );
    $last_type = $row['type'];
  }

  // Styles
  $resultset = safe_rows_start("name, md5(css) as hash", "txp_css", "1=1 order by name");
  $table .= tr(td() . tda(hed('Styles', 2), ' colspan="2"'));
  $last_type = "";
  while ($row = nextRow($resultset)) {
    $table .= tr(   
      td(($last_type !== $row['type']) ? $row['type'] . '' : '')
      . td($row['name'])
      . td(goe_hardcoded_status('css', $row['name'], $row['hash'], $row['type']))
      . goe_hardcoded_actions('css', $row['name'], $row['hash'], $row['type'])
      . "\n"
    );
    $last_type = $row['type'];
  }

  echo $table;
  echo endTable();
}

function goe_hardcoded_action_diff($class, $name, $type = FALSE) {
  $path = get_pref('goe_hardcoded_path', txpath . '/hardcoded/');
  $file = $path . ($type ? $type . "." : $class . '.') . $name . ".txt";
  switch($class) {
    case "form":
      $resultset = safe_rows_start("*", "txp_form", "name='$name'");
      $data = nextRow($resultset); 
      $code = $data['Form'];
    break;
    case "page":
      $resultset = safe_rows_start("*", "txp_page", "name='$name'");
      $data = nextRow($resultset); 
      $code = $data['user_html'];
    break;
    case "css":
      $resultset = safe_rows_start("*", "txp_css", "name='$name'");
      $data = nextRow($resultset); 
      $code = $data['css'];
    break;
  }
  echo goe_hardcoded_link('back');
  $difflibpath = get_pref('goe_hardcoded_phpdiff_path', txpath . '/vendor/phpdiff/lib');
  if (file_exists($difflibpath . "/Diff.php")) {
    $a = explode("\n", $code);
    $b = explode("\n", file_get_contents($file));
    require($difflibpath . "/Diff.php");
    $diff = new Diff($a, $b, array());
    require $difflibpath . '/Diff/Renderer/Text/Unified.php';
    $renderer = new Diff_Renderer_Text_Unified;
    echo '<textarea rows="30">' . $diff->Render($renderer) . '</textarea>';
  }
  elseif (function_exists("shell_exec")) {
    $tmp = tempnam(sys_get_temp_dir(), "TXP");
    file_put_contents($tmp, $code);
    echo '<textarea rows="30">' . shell_exec("diff -u -s --strip-trailing-cr $tmp $file") . '</textarea>';
    unlink($tmp);
  }
  else {
    echo 'Neither shell_exec nor the PHPDiff Library is available on your server. Unable to display a diff.';
  }
}

function goe_hardcoded_action_delete($class, $name, $type = FALSE) {
  $path = get_pref('goe_hardcoded_path', txpath . '/hardcoded/');
  $file = $path . ($type ? $type . "." : $class . '.') . $name . ".txt";
  unlink($file);
}

function goe_hardcoded_action_to_db($class, $name, $type = FALSE) {
  $path = get_pref('goe_hardcoded_path', txpath . '/hardcoded/');
  $file = $path . ($type ? $type . "." : $class . '.') . $name . ".txt";
  if (! is_readable($file)) return;
 
  $code = file_get_contents($file);
  switch($class) {
    case "form":
      safe_update("txp_form", "Form='$code'", "name='$name'");
    break;
    case "page":
      safe_update("txp_page", "user_html='$code'", "name='$name'", TRUE);
    break;
    case "css":
      safe_update("txp_css", "css='$code'", "name='$name'");
    break;
  }
}

function goe_hardcoded_action_to_file($class, $name, $type = FALSE) {
  $path = get_pref('goe_hardcoded_path', txpath . '/hardcoded/');
  $file = $path . ($type ? $type . "." : $class . '.') . $name . ".txt";
  switch($class) {
    case "form":
      $resultset = safe_rows_start("*", "txp_form", "name='$name'");
      $data = nextRow($resultset); 
      $code = $data['Form'];
    break;
    case "page":
      $resultset = safe_rows_start("*", "txp_page", "name='$name'");
      $data = nextRow($resultset); 
      $code = $data['user_html'];
    break;
    case "css":
      $resultset = safe_rows_start("*", "txp_css", "name='$name'");
      $data = nextRow($resultset); 
      $code = $data['css'];
    break;
  }
  file_put_contents($file, $code);
}

function goe_hardcoded_status($class, $name, $hash, $type = FALSE) {
  $path = get_pref('goe_hardcoded_path', txpath . '/hardcoded/');
  $file = $path . ($type ? $type . "." : $class . '.') . $name . ".txt";
 
  if (! is_readable($file)) {
    return GOE_HARDCODED_NOT_READABLE;
  }
  if (! is_writable($file)) {
    return GOE_HARDCODED_NOT_WRITEABLE;
  }
  if (md5_file($file) !== $hash) {
    return GOE_HARDCODED_FILE_DIFFERS;
  } 
  return GOE_HARDCODED_FILE_EXISTS;
}

function goe_hardcoded_actions($class, $name, $hash, $type=FALSE) {  
  $links = array();
  $status = goe_hardcoded_status($class, $name, $hash, $type);

  if ($status !== GOE_HARDCODED_NOT_WRITEABLE 
   && $status !== GOE_HARDCODED_FILE_EXISTS) {
    $args = array('step' => 'to_file', 'class' => $class, 'name' => $name, 'type' => $type);
    $links[] = goe_hardcoded_link('<img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAA0AAAANCAIAAAD9iXMrAAAAAXNSR0IArs4c6QAAAD5JREFUKM9j/P//PwMMMDIyMiABZCkmBuLAYFdHNED2PB41TASVQmSZ8JsKF2fC4wCc8YEsgaaNCasZmGYDALY/Ke8sa4YFAAAAAElFTkSuQmCC">', $args);
  } else {
    $links[] = '';
  }

  if ($status !== GOE_HARDCODED_NOT_READABLE
   && $status !== GOE_HARDCODED_FILE_EXISTS) {
    $args = array('step' => 'to_db', 'class' => $class, 'name' => $name, 'type' => $type);
    $links[] = goe_hardcoded_link('<img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAA0AAAANCAIAAAD9iXMrAAAAAXNSR0IArs4c6QAAAD5JREFUKM9j/P//PwMqYGRkxBRkwlQEJ3GqQ5ZGU8qESwJNhAmXInRxTCdjAmLUwAxGVotmO7IUE5HmDXZ1AF4KEiHuF6kmAAAAAElFTkSuQmCC">
', $args);
  } else {
    $links[] = '';
  }

  if ($status === GOE_HARDCODED_FILE_DIFFERS) {
    $args = array('step' => 'diff', 'class' => $class, 'name' => $name, 'type' => $type);
    $links[] = goe_hardcoded_link('<img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAA0AAAANCAIAAAD9iXMrAAAAAXNSR0IArs4c6QAAADlJREFUKM9j/P//PwMRgAnOYmRkRJNDFmFiIBJg2otVhBEuysjIiKYCWYRYexlJ9i911JHuX/q6DwAXKSP7Uy7r9QAAAABJRU5ErkJggg==">', $args);
  } else {
    $links[] = '';
  }
  
  if ($status !== GOE_HARDCODED_NOT_READABLE) {
    $args = array('step' => 'delete', 'class' => $class, 'name' => $name, 'type' => $type);
    $links[] = goe_hardcoded_link('<img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAA0AAAANCAIAAAD9iXMrAAAAAXNSR0IArs4c6QAAAEdJREFUKM+lkcEKADAIQqf//8/tEETYFo11kiclEsxsDYahAIiXCTPKhpKaeyRwWkNljbcbwtnH/fWt89yXYriuhL0dBMP/bn0mQfMc5gUdAAAAAElFTkSuQmCC">', $args);
  } else {
    $links[] = '';
  }
  
  $links = array_map('td', $links);

  return join('', $links);
}

function goe_hardcoded_link($text, $args = array(), $title = FALSE) {
  $link = array();
  $link[] = '<a href="?event=goe_hardcoded';
  foreach ($args as $key => $val) {
    $link[] = $val ? a . $key . "=" . urlencode($val) : '';
  }
  $link[] = '"';
  $link[] = $title ? 'title="'. gTxt($title) .'"' : '';
  $link[] = '>' . $text . '</a>';

  return join('', $link);
}

# --- END PLUGIN CODE ---
if (0) {
?>
<!--
# --- BEGIN PLUGIN HELP ---
h1. Textpattern hardcoded!

_This is in alpha stage! It somehow works but is quite far away from show time! 
And it badly lacks documentation._

This Module allows you to manage your page templates, forms and style 
definitions with files.

This means you can dump the contents of any of these to a file, read them from
such a file and, if applicable, inspect the diff between what is configured in the
database.

There are several use cases for this:

# Keep track of templates and styles with a version control such as git, hg, ...
# Easily deploy changes by ftp or version control rathern than perfoming 
  a copy and paste orgy.
# Check if and how data was modified in the database.

Typically you would have it installed on the dev and on the productive system.
Just hack templates and styles on the dev system until you like what you see. 
Dump them to files and move these files to the productive system, where you can
put them in effect with a single mouse click.

Read pages, forms and styles from files.
# --- END PLUGIN HELP ---
-->
<?php
}
?>
