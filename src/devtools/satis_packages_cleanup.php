<?php

  // this is the local filesystem path to the webroot of composer.tiki.org - i.e. where the dist directory lives
  $composer_packages_path = '/path/to/composer/webroot';

  $base_url = 'https://composer.tiki.org/';
  $packages_url = $base_url . 'packages.json';
  $packages = [];

  $content = parse_packages_url($packages_url);
  $packages = array_merge($packages, $content['packages']);
if (! empty($content['includes'])) {
    foreach ($content['includes'] as $file => $sha1) {
        $include_url = $base_url . $file;
        $content = parse_packages_url($include_url);
        $packages = array_merge($packages, $content['packages']);
    }
}

  $urls = [];
foreach ($packages as $package => $versions) {
    foreach ($versions as $version) {
        if (! isset($version['dist'])) {
            continue;
        }
        $urls[] = str_replace($base_url, '', $version['dist']['url']);
    }
}

  echo "Found " . count($packages) . " packages with " . count($urls) . " versions.\n";

  $directory = new \RecursiveDirectoryIterator($composer_packages_path . '/dist', \FilesystemIterator::FOLLOW_SYMLINKS);
  $filter = new \RecursiveCallbackFilterIterator($directory, function ($current, $key, $iterator) {
    // Skip hidden files and directories.
    if ($current->getFilename()[0] === '.') {
        return false;
    }
    return true;
  });
  $iterator = new \RecursiveIteratorIterator($filter);
  foreach ($iterator as $info) {
      $file = preg_replace('/^\//', '', str_replace($composer_packages_path, '', $info->getPathname()));
      if (! in_array($file, $urls)) {
          echo "$file\n";
          if (isset($argv[1]) && $argv[1] == '--cleanup') {
              unlink($info->getPathname());
          }
      }
  }

  if (! isset($argv[1]) || $argv[1] != '--cleanup') {
      echo "Script run without --cleanup option - it only lists files to be deleted. If you want to actually remove the files, run the script with --cleanup option.\n";
  }

  function parse_packages_url($url)
  {
      echo "Processing $url\n";
      $content = file_get_contents($url);
      if (empty($content)) {
          die("Empty content in packages url: $url\n");
      }
      $parsed = json_decode($content, true);
      if (empty($parsed)) {
          die("Failed parsing $url\n");
      }
      return $parsed;
  }
