<?php

\Core\Session::put('probe', 'persistent value');
\Core\Session::flash('probe', 'flash value');
$value = \Core\Session::get('probe');

?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Flash precedence probe</title>
</head>
<body>
    <h1>Flash precedence probe</h1>
    <p id="probe-value"><?= htmlspecialchars((string) $value) ?></p>
    <p>Expected result: <strong>flash value</strong>.</p>
    <p><a href="/contact/precedence">Run the probe again</a></p>
</body>
</html>
