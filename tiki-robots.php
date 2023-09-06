<?php

header('content-type: text/plain');

require_once 'tiki-setup.php';

global $base_url, $prefs;
$dynamicOptions = "";
if ($prefs['sitemap_enable'] == 'y') {
    $dynamicOptions .= "# Be sure to re-generate sitemaps with scheduler (https://doc.tiki.org/Sitemap)" . PHP_EOL;
    $dynamicOptions .= "Sitemap: {$base_url}storage/public/sitemap-index.xml";
}

echo <<<EOF
# This is a robots.txt file to instruct all search bots that we don't want them to crawl in the paths beginning with the strings below.
# If you are viewing this via example.org/robots.txt, it is an alias of tiki-robots.php (likely via an instruction in .htaccess)
# tiki-robots.php permits to dynamically generate a robots.txt file according to preferences set in the admin panel
# 
# For an installation in a subdirectory, you have to copy this file in root of your domain and add /yoursubdirname on each line.
#
# (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
#
# All Rights Reserved. See copyright.txt for details and a complete list of authors.
# Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

User-agent: *
# Uncomment the following line to indicate to robots __not__ to crawl your site.
# Disallow: /

{$dynamicOptions}

#  == Begin SEF URL Section ==
#remove pages in favour of the SEF counterpart (Enable only after SEF URL’s are enabled in tiki will otherwise prevent indexing.)
#Disallow: /tiki-forums.php
#Disallow: /tiki-view_forum.php
#Disallow: /tiki-index.php
#Disallow: /tiki-read_article.php
#Disallow: /tiki-view_blog.php
#Disallow: /tiki-list_file_gallery.php
#  == End SEF URL Section ==

#Disallow: /tiki-view_forum_thread.php  #Do Not Enable until bug5204 is fixed. Will prevent indexing. Add to SEF URL Section when bug is fixed.

#This option will filer out multiple views of Structured Wiki Pages
#only enable if "Open page as structure" is enabled under Admin-Wiki, so the structure is not passed via the url.
#If existing links in your pages use the structure= in our pages, it may cause problem with Google Crawling the website.

#Disallow: /*structure=*


# This is to slow down any crawling so as not to put pressure on your server
Crawl-delay: 10

#filter out crawling that applies in all situations
Disallow: /temp/
Allow:    /temp/public/
Disallow: /addons/
Disallow: /admin/
Disallow: /backup/
Disallow: /db/
Disallow: /doc/
Disallow: /installer/
Disallow: /lang/
Allow: /lang/*.js$
Disallow: /maps/
Disallow: /mods/
Disallow: /modules/
Disallow: /permissioncheck/
Disallow: /popups/
Disallow: /templates/
Disallow: /tests/
Disallow: /vendor*
Allow: /vendor*.gif$
Allow: /vendor*.jpg$
Allow: /vendor*.png$
Allow: /vendor*.svg$
Allow: /vendor*.js$
Allow: /vendor*.css$
Allow: /vendor*.otf$
Allow: /vendor*.eot$
Allow: /vendor*.ttf$
Allow: /vendor*.woff$
Allow: /vendor*.woff2$
Disallow: /get_strings.php
Disallow: /tiki-admin

#filer out multiple views
Disallow: /*sort_mode=*
Disallow: /*latest=1*
Disallow: /*PHPSESSID=
Disallow: /*display=print*
Disallow: /*show_comzone=*
Disallow: /*page_ref_id=*
Disallow: /*topics_offset=-1* # to fix a display error, can be removed when bug5204 is fixed
Disallow: /*show_details=*
Disallow: /*offset=0*
Disallow: /*print=y*
Disallow: /*fullscreen=y*
Disallow: /*mode=mobile*
EOF;
