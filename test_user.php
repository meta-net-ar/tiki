<?php
require_once('tiki-setup.php');
global $user;
echo "User: [" . $user . "]\n";
echo "Is logged in: " . (TikiLib::lib('user')->is_logged_in() ? 'Yes' : 'No') . "\n";
