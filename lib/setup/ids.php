<?php

//this script may only be included - so its better to die if called directly.
if (basename($_SERVER['SCRIPT_NAME']) == basename(__FILE__)) {
	header("location: index.php");
	exit;
}

$data = array(
	'GET' => $_GET,
	'POST' => $_POST,
);

$filters = new \Expose\FilterCollection();
$filters->load();

if (!empty($prefs['ids_log_to_file'])) {
	$filepath = $prefs['ids_log_to_file'];
} else {
	$filepath = 'ids.log';
}

$logger = new \IDS_log($filepath);

$manager = new \Expose\Manager($filters, $logger);
$manager->run($data);

if ($manager->getImpact() > 0) {
	$report = $manager->export();
	$logger->info("Report: " . $report);
}
