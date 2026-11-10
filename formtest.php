<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Form Data</title>
</head>
<body>
<?php
// Whitelist of feature values the form is allowed to send (matches the <option> values).
$allowedOptions = ['Chat', 'Email', 'File transfer', 'Directory', 'Shell', 'Remote control'];

// Escape anything user-controlled before printing.
function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

// Read a scalar field safely; returns $default if absent, null if malformed (e.g. sent as an array).
function field($key, $default = '') {
    if (!isset($_POST[$key])) return $default;
    return is_string($_POST[$key]) ? $_POST[$key] : null;
}

// Validate the multi-select.
$selected = [];
$optionError = '';
if (!isset($_POST['options'])) {
    // Nothing selected: browsers omit the field entirely.
} elseif (!is_array($_POST['options'])) {
    $optionError = 'Error: options must be submitted as a list.';
} else {
    foreach ($_POST['options'] as $opt) {
        if (is_string($opt) && in_array($opt, $allowedOptions, true)) {
            $selected[] = $opt;
        } else {
            $optionError = 'Error: an unknown or malformed feature value was submitted.';
            $selected = [];
            break;
        }
    }
}
?>

<h1>Form input values</h1>
<?php
$name    = field('visitor_name');
$license = isset($_POST['licenseOK']) ? 'Accepted' : 'Not accepted';
$account = field('account_type');
$system  = field('system');
$remarks = field('remarks');
?>
<p>Your Name: <?php echo $name === null ? '[invalid value]' : h($name); ?></p>
<p>Password: [PROTECTED]</p>
<p>License: <?php echo h($license); ?></p>
<p>Account type: <?php echo $account === null ? '[invalid value]' : h($account); ?></p>

<p>Options:
<?php
if ($optionError !== '')      echo h($optionError);
elseif (count($selected) === 0) echo 'No optional features selected';
else                          echo h(implode(', ', $selected));
?>
</p>

<p>Operating system: <?php echo $system === null ? '[invalid value]' : h($system); ?></p>
<p>Remarks: <?php echo $remarks === null ? '[invalid value]' : nl2br(h($remarks)); ?></p>

<h1>All Form Data</h1>
<?php
foreach ($_POST as $key => $val) {
    if ($key === 'password') continue; // never display passwords
    if (is_array($val)) {
        $parts = [];
        foreach ($val as $v) {
            $parts[] = is_string($v) ? h($v) : '[invalid value]';
        }
        echo '<p>' . h($key) . ' = ' . implode(', ', $parts) . "</p>\n";
    } else {
        echo '<p>' . h($key) . ' = ' . h($val) . "</p>\n";
    }
}
?>
</body>
</html>