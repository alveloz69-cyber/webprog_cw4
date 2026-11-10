<?php
// Exercise 5 + 4 + 3: validate first, save second, render success last.
// Nothing is sent to the browser until validation and the append have both succeeded.

// Log detailed errors privately; never show PHP warnings to visitors.
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// Small error page. Messages are fixed text, never user input.
function fail($code, $title, $message) {
    http_response_code($code);
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8">'
       . '<meta name="viewport" content="width=device-width, initial-scale=1">'
       . '<title>' . $title . '</title><link href="buyagrade.css" rel="stylesheet"></head>'
       . '<body><h1>' . $title . '</h1>'
       . '<p>' . $message . ' <a href="buyagrade.html">Try again?</a></p></body></html>';
    exit;
}

// 1. POST only. A direct GET gets guidance and never touches the file.
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    fail(405, 'Use the practice form', 'This page only processes form submissions. Please use the practice form.');
}

// 2. Presence, type, blank, and delimiter checks (before any string function runs on a non-string).
$values = [];
foreach (['name', 'section', 'cardnumber', 'cardtype'] as $field) {
    $raw = $_POST[$field] ?? null;
    if (!is_string($raw) || trim($raw) === '' || strpbrk($raw, ";\r\n") !== false) {
        fail(400, 'Sorry', "You didn't fill out the form completely.");
    }
    $values[$field] = trim($raw);
}

// 3. Known choices and card format.
if (!in_array($values['section'], ['MA', 'MB', 'MC', 'MD', 'ME', 'MF', 'MG', 'MH'], true)
    || !in_array($values['cardtype'], ['visa', 'mastercard'], true)
    || preg_match('/\A[0-9]{16}\z/', $values['cardnumber']) !== 1) {
    fail(400, 'Sorry', "You didn't fill out the form correctly. Choose a listed section and card type, and enter 16 practice digits.");
}

// 4. Persist (valid requests only). Storage failure is a 500, not a validation error.
$path = __DIR__ . '/suckers.html';
$line = implode(';', [
    $values['name'], $values['section'],
    $values['cardnumber'], $values['cardtype']
]) . PHP_EOL;

$written = file_put_contents($path, $line, FILE_APPEND | LOCK_EX);
if ($written === false || $written !== strlen($line)) {
    error_log('Lab record append failed or was incomplete.');
    http_response_code(500);
    exit('The record could not be saved completely. Ask your instructor to check storage before retrying.');
}

$all = file_get_contents($path);
if ($all === false) {
    error_log('Lab record read failed after a successful append.');
    http_response_code(500);
    exit('Your record was saved, but the records could not be displayed. Do not resubmit.');
}
?>
<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<title>Buy Your Way to a Better Education!</title>
		<link href="buyagrade.css" rel="stylesheet">
	</head>

	<body>
		<h1>Thanks, sucker!</h1>

		<p>Your information has been recorded.</p>

		<dl>
			<dt>Name</dt>
			<dd><?= htmlspecialchars($values['name'], ENT_QUOTES, 'UTF-8') ?></dd>

			<dt>Section</dt>
			<dd><?= htmlspecialchars($values['section'], ENT_QUOTES, 'UTF-8') ?></dd>

			<dt>Credit Card</dt>
			<dd><?= htmlspecialchars($values['cardnumber'], ENT_QUOTES, 'UTF-8') ?>
				(<?= htmlspecialchars($values['cardtype'], ENT_QUOTES, 'UTF-8') ?>)</dd>
		</dl>

		<p>Here are all the suckers who have submitted here:</p>
		<pre><?= htmlspecialchars($all, ENT_QUOTES, 'UTF-8') ?></pre>
	</body>
</html>