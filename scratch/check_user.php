<?php
require_once('tiki-setup.php');
global $user;
echo "User: [" . $user . "]\n";
echo "Is set: " . (isset($user) ? 'yes' : 'no') . "\n";
echo "Is empty: " . (empty($user) ? 'yes' : 'no') . "\n";
if ($user === null) echo "Is null\n";
