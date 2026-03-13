<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
use Tiki\File\PDFHelper;
use Tiki\Lib\Auth\Tokens;
use Tiki\Package\ComposerManager;

class Tracker_Field_Files extends \Tracker\Field\AbstractItemField implements \Tracker\Field\ExportableInterface
{
    protected $fileIdsToBeAttached = [];

    public static function getManagedTypesInfo(): array
    {
        global $prefs;

        $options = [
            'FG' => [
                'name' => tr('Files'),
                'description' => tr('Attached and upload files stored in the file galleries to the tracker item.'),
                'prefs' => ['trackerfield_files', 'feature_file_galleries'],
                'tags' => ['advanced'],
                'help' => 'Files-Tracker-Field',
                'default' => 'y',
                'params' => [
                    'galleryId' => [
                        'name' => tr('Gallery ID'),
                        'description' => tr('File gallery to upload new files into.'),
                        'filter' => 'int',
                        'legacy_index' => 0,
                        'profile_reference' => 'file_gallery',
                    ],
                    'directoryPattern' => [
                        'name' => tr('Directory pattern'),
                        'description' => tr('Only valid with direct mapping file gallery storage - choose a field describing the (sub)directory pattern to save the uploaded files into. Could be a text or math calculation field.'),
                        'filter' => 'int',
                        'profile_reference' => 'tracker_field',
                        'parent' => 'input[name=trackerId]',
                        'parentkey' => 'tracker_id',
                        'sort_order' => 'position_nasc',
                    ],
                    'filter' => [
                        'name' => tr('MIME Type Filter'),
                        'description' => tr('Mask for accepted MIME types in the field'),
                        'filter' => 'text',
                        'legacy_index' => 1,
                    ],
                    'namefilter' => [
                        'name' => tr('File Name Filter'),
                        'description' => tr('Regular expression that uploaded file name should match in order to be accepted for upload.'),
                        'filter' => 'text',
                    ],
                    'namefilterError' => [
                        'name' => tr('File Name Filter Error Message'),
                        'description' => tr('Text of the error message when file name filter doesn\'t match.'),
                        'filter' => 'text',
                        'depends' => [
                            'field' => 'namefilter'
                        ],
                    ],
                    'count' => [
                        'name' => tr('File Count'),
                        'description' => tr('Maximum number of files to be attached on the field.'),
                        'filter' => 'int',
                        'legacy_index' => 2,
                    ],
                    'excessBehavior' => [
                        'name' => tr('Excess File Upload Behavior'),
                        'description' => tr('Determines the system behavior when user uploads more than the allowed number of files.'),
                        'filter' => 'text',
                        'default' => 'discard',
                        'options' => [
                            'discard' => 'Discard extra uploaded files and use the latest one(s)',
                            'split' => 'Allow upload and split the files in separate tracker items'
                        ],
                        'depends' => [
                            'field' => 'count'
                        ],
                    ],
                    'displayMode' => [
                        'name' => tr('Display Mode'),
                        'description' => tr('Show files as object links or via a wiki plugin (img so far)'),
                        'filter' => 'word',
                        'options' => [
                            '' => tr('Links'),
                            'barelink' => tr('Bare Links'),
                            'table' => tr('Table'),
                            'img' => tr('Images'),
                            'vimeo' => tr('Vimeo'),
                            'googleviewer' => tr('Google Viewer'),
                            'moodlescorm' => tr('Moodle Scorm Viewer'),
                        ],
                    ],
                    'displayParams' => [
                        'name' => tr('Display parameters'),
                        'description' => tr('URL-encoded parameters used such as in the {img} plugin, for example,.') . ' "max=400&desc=namedesc&stylebox=block"',
                        'filter' => 'text',
                    ],
                    'displayParamsForLists' => [
                        'name' => tr('Display parameters for lists'),
                        'description' => tr('URL-encoded parameters used such as in the {img} plugin, for example,.') . ' "thumb=box&max=60"',
                        'filter' => 'text',
                    ],
                    'displayOrder' => [
                        'name' => tr('Display Order'),
                        'description' => tr('Sort order for the files'),
                        'filter' => 'word',
                        'options' => [
                            '' => tr('Default (order added to tracker item)'),
                            'name_asc' => tr('Name (A - Z)'),
                            'name_desc' => tr('Name (Z - A)'),
                            'filename_asc' => tr('Filename (A - Z)'),
                            'filename_desc' => tr('Filename (Z - A)'),
                            'created_asc' => tr('Created date (old - new)'),
                            'created_desc' => tr('Created date (new - old)'),
                            'lastModif_asc' => tr('Last modified date (old - new)'),
                            'lastModif_desc' => tr('Last modified date (new - old)'),
                            'filesize_asc' => tr('File size (small - large)'),
                            'filesize_desc' => tr('File size (large - small)'),
                            'hits_asc' => tr('Hits (low - high)'),
                            'hits_desc' => tr('Hits (high - low)'),
                        ],
                    ],
                    'deepGallerySearch' => [
                        'name' => tr('Include Child Galleries'),
                        'description' => tr('Use files from child galleries as well.'),
                        'filter' => 'int',
                        'options' => [
                            0 => tr('No'),
                            1 => tr('Yes'),
                        ],
                    ],
                    'replace' => [
                        'name' => tr('Replace Existing File'),
                        'description' => tr('Replace the existing file, if any, instead of uploading a new one.'),
                        'filter' => 'alpha',
                        'default' => 'n',
                        'options' => [
                            'n' => tr('No'),
                            'y' => tr('Yes'),
                        ],
                    ],
                    'browseGallery' => [
                        'name' => tr('Offer to Browse Gallery'),
                        'description' => tr('Let users browse a File Gallery for selecting a file which is already in a Tiki File Gallery.') ,
                        'filter' => 'alpha',
                        'default' => 'y',
                        'options' => [
                            'n' => tr('No'),
                            'y' => tr('Yes'),
                        ],
                    ],
                    'browseGalleryId' => [
                        'name' => tr('Browse Gallery ID'),
                        'description' => tr('File gallery browse files. Use 0 for root file gallery. (requires elFinder feature - experimental)') . '. ' . tr('To hide the button: Disable "Offer to Browse Gallery" or restrict permissions to view the file gallery.') ,
                        'filter' => 'int',
                        'profile_reference' => 'file_gallery',
                    ],
                    'duplicateGalleryId' => [
                        'name' => tr('Duplicate Gallery ID'),
                        'description' => tr('File gallery to duplicate files into when copying the tracker item. 0 or empty means do not duplicate (default).'),
                        'filter' => 'int',
                        'profile_reference' => 'file_gallery',
                    ],
                    'fileGalleryPerTrackerItem' => [
                        'name' => tr('File Gallery per Tracker Item'),
                        'description' => tr('Create a sub-gallery for each tracker item.'),
                        'filter' => 'alpha',
                        'default' => 'n',
                        'options' => [
                            'n' => tr('No'),
                            'y' => tr('Yes'),
                        ],
                    ],
                    'fileGalleryPerTrackerItemName' => [
                        'name' => tr('File Gallery per Tracker Item'),
                        'description' => tr('Pattern of the name of sub-gallery to be created. Default value is "Field %fieldId% item %itemId%" where %fieldId% is the fieldId and %itemId% is the itemId. The gallery names will then be, for example "Events Photos item: 42".'),
                        'filter' => 'text',
                    ],
                    'indexGeometry' => [
                        'name' => tr('Index As Map Layer'),
                        'description' => tr('Index the files in a specific format for use in map searchlayers to display trails and features.'),
                        'filter' => 'text',
                        'default' => '',
                        'options' => [
                            '' => tr('No'),
                            'geojson' => tr('GeoJSON'),
                            'gpx' => tr('GPX'),
                        ],
                    ],
                    'uploadInModal' => [
                        'name' => tr('Upload In Modal'),
                        'description' => tr('Upload files in a new modal window.'),
                        'filter' => 'alpha',
                        'default' => 'y',
                        'options' => [
                            'n' => tr('No'),
                            'y' => tr('Yes'),
                        ],
                    ],
                    'image_x' => [
                        'name' => tr('Maximum image width'),
                        'description' => tr('Leave blank to use selected gallery default setting or enter value in pixels to override gallery settings'),
                        'filter' => 'text',
                        'default' => '',
                    ],
                    'image_y' => [
                        'name' => tr('Maximum image height'),
                        'description' => tr('Leave blank to use selected gallery default settings or enter value in pixels to override gallery settings'),
                        'filter' => 'text',
                    ],
                    'addDecriptionOnUpload' => [
                        'name' => tr('Add Descriptions'),
                        'description' => tr('Add descriptions on uploaded files.'),
                        'filter' => 'alpha',
                        'default' => 'n',
                        'options' => [
                            'n' => tr('No'),
                            'y' => tr('Yes'),
                        ],
                    ],
                    'requireTitle' => [
                        'name' => tr('Require file title'),
                        'description' => tr('Require a file title which will be saved as the name of the file in the file gallery in addition to the filename. Upload In Modal required.'),
                        'filter' => 'alpha',
                        'default' => 'n',
                        'options' => [
                            'n' => tr('No'),
                            'y' => tr('Yes'),
                        ],
                    ],
                    'liveRecord' => [
                        'name' => tr('Live Record'),
                        'description' => tr('Use the browser to record a video, an audio or a screen capture.'),
                        'filter' => 'alpha',
                        'default' => 'n',
                        'options' => [
                            'n' => tr('No'),
                            'audio' => tr('Audio only'),
                            'screen' => tr('Screen only'),
                            'screenandaudio' => tr('Screen and audio'),
                            'cameraandaudio' => tr('Camera and audio'),
                        ],
                    ],
                    'changeFileNameOnUpload' => [
                        'name' => tr('Change file name'),
                        'description' => tr('Change file name on uploaded files. Upload In Modal required.'),
                        'filter' => 'alpha',
                        'default' => 'n',
                        'options' => [
                            'n' => tr('No'),
                            'y' => tr('Yes'),
                        ],
                    ],
                ],
            ],
        ];
        if (isset($prefs['vimeo_upload']) && $prefs['vimeo_upload'] === 'y') {
            $options['FG']['params']['displayMode']['description'] = tr('Show files as object links or via a wiki plugin (img, Vimeo)');
            $options['FG']['params']['displayMode']['options']['vimeo'] = tr('Vimeo');
        }
        return $options;
    }

    public function getFieldData(array $requestData = []): array
    {
        global $prefs, $user;
        $filegallib = TikiLib::lib('filegal');

        $galleryId = (int) $this->getOption('galleryId');

        //If there is no gallery choosen we'll take the last used gallery
        if ($galleryId == 0 && ! empty($_SESSION['lastUploadGalleryId'])) {
            $galleryId = (int) $_SESSION['lastUploadGalleryId'];
        }
        $count = (int) $this->getOption('count');
        $excessBehavior = $this->getOption('excessBehavior');
        $deepGallerySearch = (bool) $this->getOption('deepGallerySearch');

        // to use the user's userfiles gallery enter the fgal_root_user_id which is often (but not always) 2
        $galleryId = $filegallib->check_user_file_gallery($galleryId);

        $value = '';
        $ins_id = $this->getInsertId();
        if (isset($requestData[$ins_id])) {
            // Incoming data from form

            // Get the list of selected file IDs from the text field
            $value = $requestData[$ins_id];
            if (! is_array($value)) {
                $fileIds = explode(',', $value);
            } else {
                $fileIds = $value;
            }

            // Remove missed uploads
            $fileIds = array_filter($fileIds);

            // Obtain the info for display and filter by type if specified
            $fileInfo = $this->getFileInfo($fileIds);
            $fileInfo = array_filter($fileInfo, [$this, 'filterFile']);

            // Rebuild the database value, but preserve the order the files have been attached to the item
            foreach ($fileIds as & $fileId) {
                if (! isset($fileInfo[$fileId])) {
                    $fileId = 0;
                }
            }

            $value = implode(',', array_filter($fileIds));
        } else {
            $value = $this->getValue();

            // Obtain the information from the database for display
            $fileIds = array_filter(explode(',', $value));
            $fileInfo = $this->getFileInfo($fileIds);
        }

        if ($deepGallerySearch) {
            $gallery_list = null;
            $filegallib->getGalleryIds($gallery_list, $galleryId, 'list');
            $gallery_list = implode(' or ', $gallery_list);
        } else {
            $gallery_list = $galleryId;
        }

        if ($this->getOption('displayMode') == 'img' && $fileIds) {
            $firstfile = $fileIds[0];
        } else {
            $firstfile = 0;
        }

        $galinfo = $filegallib->get_file_gallery($galleryId);
        if (! $galinfo) {
            Feedback::error(tr('Files field: Gallery #%0 not found', $galleryId));
            return [];
        }

        //If fileGalleryPerTrackerItem is set to yes
        if ($this->trackerField->getOption('fileGalleryPerTrackerItem')) {
            //Check permissions related to a specific gallery object
            $perms = Perms::get('file gallery', $galleryId);
            $canCreateGallery = $perms->create_file_galleries;
        }

        if ($prefs['feature_use_fgal_for_user_files'] !== 'y' || $galinfo['type'] !== 'user') {
            $perms = Perms::get('file gallery', $galleryId);
            $canUpload = $perms->upload_files;
        } else {
            $perms = TikiLib::lib('tiki')->get_local_perms($user, $galleryId, 'file gallery', $galinfo, false);     //get_perm_object($galleryId, 'file gallery', $galinfo);
            $canUpload = $perms['tiki_p_upload_files'] === 'y';
        }

        $image_x = $this->getOption('image_x');
        $image_y = $this->getOption('image_y');

        //checking if image_x and image_y are set
        if (! $image_x) {
            $image_x = $galinfo['image_max_size_x'];
        }

        if (! $image_y) {
            $image_y = $galinfo['image_max_size_y'];
        }

        if ($this->getOption('directoryPattern')) {
            $trk = TikiLib::lib('trk');
            $field = $trk->get_tracker_field($this->getOption('directoryPattern'));
            $directoryPattern = $trk->get_field_value($field, $this->getItemData());
        } else {
            $directoryPattern = '';
        }

        return [
            'galleryId' => $galleryId,
            'canUpload' => $canUpload,
            'canCreateGallery' => $canCreateGallery,
            'limit' => $count,
            'excessBehavior' => $excessBehavior,
            'files' => $fileInfo,
            'firstfile' => $firstfile,
            'value' => $value,
            'filter' => $this->getOption('filter'),
            'namefilter' => $this->getOption('namefilter'),
            'namefilterError' => $this->getOption('namefilterError'),
            'image_x' => $image_x,
            'image_y' => $image_y,
            'gallerySearch' => $gallery_list,
            'requireTitle' => $this->getOption('requireTitle'),
            'directoryPattern' => $directoryPattern,
            'fileGalleryPerTrackerItem' => $this->trackerField->getOption('fileGalleryPerTrackerItem'),

        ];
    }

    public function renderInput($context = [])
    {
        global $prefs;

        $context['canBrowse'] = false;

        if ($prefs['fgal_tracker_existing_search'] == 'y' && $this->getOption('browseGallery') != 'n') {
            if ($this->getOption('browseGalleryId')) {
                $defaultGalleryId = $this->getOption('browseGalleryId');
            } elseif ($this->getOption('galleryId')) {
                $defaultGalleryId = $this->getOption('galleryId');
            } else {
                $defaultGalleryId = 0;
            }
            $defaultVolumeId = TikiLib::lib('filegal')->getGallerySpecialRoot($defaultGalleryId);
            if (! $defaultVolumeId) {
                // assume normal filegals if $defaultGalleryId is empty
                $defaultVolumeId = $prefs['fgal_root_id'];
            }
            $deepGallerySearch = $this->getOption('deepGallerySearch');
            //in case $deepGallerySearch is false
            $deepGallerySearch = $deepGallerySearch == 1 ? 1 : 0;
            $image_x = $this->getOption('image_x');
            $image_y = $this->getOption('image_y');

            if ($prefs['fgal_elfinder_feature'] == 'y') {
                include_once 'lib/jquery_tiki/elfinder/tikiElFinder.php';
                tikiElFinder::loadJSCSS();
                $smarty = TikiLib::lib('smarty');
                $context['onclick'] = 'return openElFinderDialog(this, {
    defaultGalleryId:' . $defaultGalleryId . ',
    deepGallerySearch: ' . $deepGallerySearch . ',
    defaultVolumeId:' . $defaultVolumeId . ',
    ticket: \'' . smarty_function_ticket(['mode' => 'get'], $smarty->getEmptyInternalTemplate()) . '\',
    getFileCallback: function(file,elfinder){ window.handleFinderFile(file,elfinder); },
    eventOrigin:this
});';
            }
            $context['galleryId'] = $defaultGalleryId;
            $context['canBrowse'] = Perms::get(['type' => 'file gallery', 'object' => $defaultGalleryId])->view_file_gallery;
        }

        if ($this->getOption('uploadInModal') === 'n') {
            TikiLib::lib('access')->setTicket();
        }

        return $this->renderTemplate('trackerinput/files.tpl', $context, [
            'replaceFile' => 'y' == $this->getOption('replace', 'n'),
            'addDecriptionOnUpload' => $this->getOption('addDecriptionOnUpload') === 'y' ? 1 : 0,
            'changeFileNameOnUpload' => $this->trackerField->getOption('changeFileNameOnUpload') === 'y' ? 1 : 0,
        ]);
    }

    public function renderOutput($context = [])
    {
        global $prefs;
        global $mimetypes;
        global $tikipath;


        include('lib/mime/mimetypes.php');
        $galleryId = (int)$this->getOption('galleryId');

        if (! isset($context['list_mode'])) {
            $context['list_mode'] = 'n';
        }
        if (! $this->getOption('displayOrder')) {
            $value = $this->getValue();
        } else {
            $value = $this->getConfiguration('files');
            $value = implode(',', array_keys($value));
        }

        if ($context['list_mode'] === 'csv') {
            return $value;
        }

        if ($context['list_mode'] === 'text') {
            return implode(
                "\n",
                array_map(
                    function ($file) {
                        return $file['name'];
                    },
                    $this->getConfiguration('files')
                )
            );
        }

        $ret = '';
        if (empty($value)) {
            $ret = '&nbsp;';
        } else {
            if ($this->getOption('displayMode')) { // images etc
                $params = [
                    'fileId' => $value,
                ];
                if ($context['list_mode'] === 'y') {
                    $otherParams = $this->getOption('displayParamsForLists');
                } else {
                    $otherParams = $this->getOption('displayParams');
                }
                if ($otherParams) {
                    parse_str($otherParams, $otherParams);
                    $params = array_merge($params, $otherParams);
                }
                $params['fromFieldId'] = $this->getConfiguration('fieldId');
                $params['fromItemId'] = $this->getItemId();
                $itemData = $this->getItemData() ?? [];

                if (! isset($itemData['trackerId'], $itemData['itemId'])) {
                    $definition = $this->getFieldDefinition();

                    if (isset($definition['trackerId'])) {
                        $itemData['trackerId'] = $definition['trackerId'];
                    }
                }

                $item = Tracker_Item::fromInfo($itemData);
                $params['checkItemPerms'] = $item->canModify() ? 'n' : 'y';

                if ($this->getOption('displayMode') == 'img') { // img
                    if ($context['list_mode'] === 'y') {
                        $params['thumb'] = $context['list_mode'];
                        $params['rel'] = 'box[' . $this->getInsertId() . ']';
                    }
                    include_once('lib/wiki-plugins/wikiplugin_img.php');
                    $ret = TikiLib::lib('parser')->invokePlugin('img', '', $params);
                    $ret = WikiPlugin_Helper::resultString($ret);
                } elseif ($this->getOption('displayMode') == 'vimeo') { // Vimeo videos stored as filegal REMOTEs
                    include_once('lib/wiki-plugins/wikiplugin_vimeo.php');
                    $ret = TikiLib::lib('parser')->invokePlugin('vimeo', '', $params);
                } elseif ($this->getOption('displayMode') == 'moodlescorm') {
                    include_once('lib/wiki-plugins/wikiplugin_playscorm.php');
                    foreach ($this->getConfiguration('files') as $fileId => $file) {
                        $params['fileId'] = $fileId;
                        $ret .= TikiLib::lib('parser')->invokePlugin('playscorm', '', $params);
                    }
                } elseif ($this->getOption('displayMode') == 'googleviewer') {
                    if ($prefs['auth_token_access'] != 'y') {
                        $ret = tra('Token access needs to be enabled for Google viewer to be used');
                    } else {
                        $files = [];
                        foreach ($this->getConfiguration('files') as $fileId => $file) {
                            global $base_url, $tikiroot, $https_mode;
                            if ($https_mode) {
                                $scheme = 'https';
                            } else {
                                $scheme = 'http';
                            }
                            $googleurl = $scheme . "://docs.google.com/viewer?url=";
                            if ($prefs['feature_sefurl'] === 'y') {
                                $fileurl = urlencode($base_url . "dl" . $fileId);
                            } else {
                                $fileurl = urlencode($base_url . "tiki-download_file.php?fileId=" . $fileId);
                            }
                            $tokenlib = Tokens::build($prefs);
                            if ($prefs['feature_sefurl'] === 'y') {
                                $token = $tokenlib->createToken(
                                    $tikiroot . "dl" . $fileId,
                                    [],
                                    ['Registered'],
                                    ['timeout' => 600, 'hits' => 6]
                                );
                                $fileurl .= urlencode("?TOKEN=" . $token);
                            } else {
                                $token = $tokenlib->createToken(
                                    $tikiroot . "tiki-download_file.php",
                                    ['fileId' => $fileId],
                                    ['Registered'],
                                    ['timeout' => 600, 'hits' => 6]
                                );
                                $fileurl .= urlencode("&TOKEN=" . $token);
                            }
                            $url = $googleurl . $fileurl . '&embedded=true';
                            $title = $file['name'];
                            $files[] = ['url' => $url, 'title' => $title, 'id' => $fileId];
                        }
                        $smarty = TikiLib::lib('smarty');
                        $smarty->assign('files', $files);
                        $ret = $smarty->fetch('trackeroutput/files_googleviewer.tpl');
                    }
                } elseif ($this->getOption('displayMode') == 'barelink') {
                        $smarty = TikiLib::lib('smarty');
                    foreach ($this->getConfiguration('files') as $fileId => $file) {
                        $ret .= smarty_modifier_sefurl($file['fileId'], 'file');
                    }
                } elseif ($this->getOption('displayMode') == 'table') {
                    $ret = $this->renderTemplate('trackeroutput/files_table.tpl', $context, [
                        'files' => $this->getConfiguration('files')
                    ]);
                }
                $ret = preg_replace('/~\/?np~/', '', $ret);
            } else {
                $smarty = TikiLib::lib('smarty');
                $composerManager = new ComposerManager($tikipath);
                $pdfjsIsInstalled = $composerManager->isInstalled("npm-asset/pdfjs-dist-viewer-min");

                $ret = '<ol class="tracker-item-files">';

                foreach ($this->getConfiguration('files') as $fileId => $file) {
                    $ret .= '<li>';
                    if ($prefs['vimeo_upload'] == 'y' && $this->getOption('displayMode') == 'vimeo') {
                        $ret .= smarty_function_icon(['name' => 'vimeo'], $smarty->getEmptyInternalTemplate());
                    } elseif (str_starts_with($file['filetype'], 'audio/')) {
                        $ret .= smarty_function_inline_audio_player(['fileId' => $fileId], $smarty->getEmptyInternalTemplate());
                    } else {
                        $ret .= smarty_modifier_iconify('tiki-download_file.php?fileId=' . $fileId, $file['filetype'], $fileId, 2);
                    }

                    $ret .= smarty_function_object_link(['type' => 'file', 'id' => $fileId, 'title' => $file['name'], 'class' => 'label'], $smarty->getEmptyInternalTemplate());

                    $globalperms = Perms::get([ 'type' => 'file gallery', 'object' => $galleryId ]);

                    $viewicon = smarty_function_icon(['name' => 'view'], $smarty->getEmptyInternalTemplate());

                    if ($file['filetype'] == $mimetypes["pdf"] || (PDFHelper::canConvertToPDF($file['filetype']) && $prefs['fgal_convert_documents_pdf'] == 'y')) {
                        if ($pdfjsIsInstalled && $prefs['fgal_pdfjs_feature'] == 'y') {
                            $ret .= " <a href='tiki-display.php?fileId=" . $file['fileId']
                                . "' target='_blank' class='tips' title='Preview: " . $file['filename'] . "'>
                                $viewicon
                            </a>";
                        }
                    } elseif ($this->isMimeType($mimetypes, $file['filetype'], ['mp3', 'oga', 'webm', 'mp4', 'wmv', 'ogv'])) {
                        global $base_url;
                        $src = smarty_modifier_sefurl($file['fileId'], 'display');
                        $fileurl = $base_url . $src;

                        $ret .= "<div style='display:none'>
                            <div id='inline-file-$fileId'>";
                        if ($this->isMimeType($mimetypes, $file['filetype'], ['mp3', 'oga'])) {
                            $tag = 'audio';
                            $style = "data-box-width='35%' data-box-height='20%'";
                        } else {
                            $tag = 'video';
                            $style = '';
                        }
                        $ret .= "<{$tag} controls style='width : 100%'>
                                    <source src='{$fileurl}' type='{$file['filetype']}'>
                                </{$tag}>
                            </div>
                        </div>
                        <a class='cboxInlineMedia' $style title='{$tag} preview' href='#inline-file-$fileId'>$viewicon</a>";
                    } else {
                        $smarty->assign('fieldId', $this->getConfiguration('fieldId'));
                        $smarty->assign('menu_icon', $prefs['use_context_menu_icon']);
                        $smarty->assign('menu_text', $prefs['use_context_menu_text']);
                        $smarty->assign('file', $file);
                        $smarty->assign('canUpload', $globalperms->upload_files);
                        $perms = Perms::get('file', $fileId);
                        $smarty->assign('canAssignPerms', $perms->assign_perm_file_gallery);
                        $text = $smarty->fetch('tracker/fileTrackerContextMenu.tpl');
                        $popup = smarty_function_popup([
                            'fullhtml' => '1',
                            'text' => $text,
                            'trigger' => 'click'
                        ], $smarty->getEmptyInternalTemplate());
                        $icon = smarty_function_icon(['name' => 'wrench'], $smarty->getEmptyInternalTemplate());
                        $ret .= " <a class='fgalname tips label' title='" . tr('Actions') . "'href='#' $popup>$icon</a>";
                    }

                    $ret .= '</li>';
                }
                $ret .= '</ol>';
            }
        }
        return $ret;
    }

    private function isMimeType(array $mimes, string $fileType, array $extensions): bool
    {
        foreach ($extensions as $ext) {
            if ($mimes[$ext] == $fileType) {
                return true;
            }
        }
        return false;
    }

    public function handleSave($value, $oldValue)
    {
        global $prefs, $user;
        $filegallib = TikiLib::lib('filegal');
        $relationlib = TikiLib::lib('relation');
        $utilities = new Services_File_Utilities();
        $excessBehavior = $this->getOption('excessBehavior');
        $count = (int) $this->getOption('count');

        $fileIds = array_filter(explode(',', (string) $value));

        if ($count) {
            if ($excessBehavior === 'split') {
                // take the ones above the maximum for handleFinalSave to attach to new items
                $this->fileIdsToBeAttached = array_slice($fileIds, $count);
                $fileIds = array_slice($fileIds, 0, $count);
            } else {
                // Keep only the last files if a limit is applied and the rest are discarded
                $fileIds = array_slice($fileIds, -$count);
            }
        }

        $new = array_diff($fileIds, explode(',', (string) $oldValue));
        $remove = array_diff(explode(',', (string) $oldValue), $fileIds);
        //If the option to create sub-galleries and move files is enabled
        if (! empty($value) && $this->trackerField->getOption('fileGalleryPerTrackerItem') === 'y') {
            //Create new gallery and move all uploaded files
            $fieldId = $this->getConfiguration('fieldId');
            $itemId = $this->getItemId();
            $galleryName = 'Field ' . $fieldId . ' Item ' . $itemId; //By default the pattern of gallery to be created: Field [FIELD_ID] Item [ITEM_ID]
            if (! empty($this->trackerField->getOption('fileGalleryPerTrackerItemName'))) {
                $galleryName = $this->trackerField->getOption('fileGalleryPerTrackerItemName');
                $galleryName = preg_replace(['/%fieldId%/', '/%itemId%/'], [$fieldId, $itemId], $galleryName, 1);
            }

            $parentId = (int) $this->trackerField->getOption('galleryId');
            //If there is no gallery choosen we'll take 'File Galleries' as gallery where the new one will be added
            if ($parentId == 0) {
                $parentId = $prefs['fgal_root_id'];
            }

            // Get relations between the current item and the sub-galleries
            $relations = $relationlib->get_relations_from('trackeritem', $itemId, 'tiki.filegallery.attach');
            foreach ($relations as $relation) {
                if ($relation['fieldId'] === $fieldId) {
                    $galleryId = (int) $relation['itemId'];
                    break;
                }
            }

            // Create a sub-gallery if none exists for the current Field
            if (empty($galleryId)) {
                if ($parentId == $prefs['fgal_root_id']) {
                    $galleryId = $filegallib->replace_file_gallery(['name' => $galleryName, 'user' => $user]);
                } else {
                    $galleryId = $filegallib->duplicate_file_gallery($parentId, $galleryName, '', $parentId);
                }
                // Copy any direct permissions of the parent gallery
                $utilities->copyParentPermissions($parentId, $galleryId);
                // Add relation between the item and the new sub-gallery
                $relationlib->add_relation('tiki.filegallery.attach', 'trackeritem', $itemId, 'file gallery', $galleryId, false, $fieldId);
            }

            $gal_info = $filegallib->get_file_gallery($galleryId);

            if (! empty($gal_info)) {
                //Move all files to the dedicated gallery
                $filegallib->moveFilesToNewGallery($fileIds, $gal_info['galleryId']);
            } else {
                throw new Exception(tr('Gallery ID %0 not found', $galleryId));
            }
        }

        $itemId = $this->getItemId();

        if ($itemId) {
            $relationlib = TikiLib::lib('relation');
            $relations = $relationlib->get_relations_from('trackeritem', $itemId, 'tiki.file.attach');
            foreach ($relations as $existing) {
                if ($existing['type'] != 'file') {
                    continue;
                }

                if (in_array($existing['itemId'], $remove)) {
                    $relationlib->remove_relation($existing['relationId']);
                }
            }

            foreach ($new as $fileId) {
                if (! empty($fileId)) {
                    $relationlib->add_relation('tiki.file.attach', 'trackeritem', $itemId, 'file', $fileId);
                }
            }
        }

        return [
            'value' => implode(',', $fileIds),
        ];
    }

    /**
     * Implement excessBehavior = split - create additonal clones of the tracker item to store
     * excessive files attached to this one.
     */
    public function handleFinalSave(array &$data)
    {
        $fileIdsForCurrentItem = $data[$this->getConfiguration('permName')];
        if ($this->getOption('excessBehavior') === 'split' && ! empty($this->fileIdsToBeAttached)) {
            $utilities = new Services_Tracker_Utilities();
            foreach (array_chunk($this->fileIdsToBeAttached, $this->getOption('count')) as $fileIdsPerItem) {
                $data[$this->getConfiguration('permName')] = implode(',', $fileIdsPerItem);
                $itemId = $utilities->insertItem($this->getTrackerDefinition(), [
                    'status' => $this->getData('status'),
                    'fields' => $data,
                    'validate' => false,
                    'bulk_import' => true,
                ]);
            }
        }
        return $fileIdsForCurrentItem;
    }

    public function bindFiles($values, $attach = true)
    {
        $oldValues = array_filter(explode(',', $this->getValue()));
        $incomingValues = array_filter(explode(',', $values));

        if ($attach) {
            $newValues = array_merge($oldValues, $incomingValues);
        } else {
            $newValues = array_diff($oldValues, $incomingValues);
        }

        return implode(',', $newValues);
    }

    public function postSaveHook($deletedFiles)
    {
        if ($deletedFiles) {
            $filegallib = TikiLib::lib('filegal');
            $deletedFiles = explode(',', $deletedFiles);

            foreach ($deletedFiles as $fileId) {
                $fileInfo = $filegallib->get_file_info($fileId);
                $filegallib->remove_file($fileInfo);
            }
        }
    }

    /**
     * called from action_clone_item and duplicates the related files if option duplicateGalleryID is set
     */
    public function handleClone($strict = false)
    {
        global $prefs;

        $oldValue = $this->getValue();
        if ($galleryId = $this->getOption('duplicateGalleryId')) {
            $filegallib = TikiLib::lib('filegal');

            // to use the user's userfiles gallery enter the fgal_root_user_id which is often (but not always) 2
            $galleryId = $filegallib->check_user_file_gallery($galleryId);

            $newIds = [];

            foreach (array_filter(explode(',', $oldValue)) as $fileId) {
                $newIds[] = $filegallib->duplicate_file($fileId, $galleryId);
            }

            return $this->handleSave(implode(',', $newIds), $oldValue);
        }
        return [
            'value' => $oldValue,
        ];
    }

    public function watchCompare($old, $new)
    {
        $isVisible = $this->getConfiguration('isHidden', 'n') == 'n';
        if (! $isVisible) {
            return;
        }

        $filegallib = TikiLib::lib('filegal');

        $oldFileIds = explode(',', $old);
        $newFileIds = explode(',', $new);

        $oldFileInfos = empty($oldFileIds) ? [] : $filegallib->get_files_info(null, $oldFileIds);
        $newFileInfos = empty($newFileIds) ? [] : $filegallib->get_files_info(null, $newFileIds);

        $mapping = [];
        foreach ($oldFileInfos as $info) {
            $mapping[$info['fileId']] = $info['filename'];
        }
        foreach ($newFileInfos as $info) {
            $mapping[$info['fileId']] = $info['filename'];
        }

        return parent::watchCompareList($oldFileIds, $newFileIds, function ($item) use ($mapping) {
            return $mapping[$item];
        });
    }

    public function renderDiff($context = [])
    {
        $smarty = TikiLib::lib('smarty');

        if ($context['oldValue']) {
            $old = $context['oldValue'];
        } else {
            $old = '';
        }
        if ($context['value']) {
            $new = $context['value'];
        } else {
            $new = $this->getValue('');
        }
        if (empty($context['diff_style'])) {
            $context['diff_style'] = 'sidediff';
        }

        $filegallib = TikiLib::lib('filegal');

        $oldFileIds = explode(',', $old);
        $newFileIds = explode(',', $new);

        $filesRemoved = array_diff($oldFileIds, $newFileIds);
        $filesAdded = array_diff($newFileIds, $oldFileIds);

        $addedFileInfos = empty($filesAdded) ? [] : $filegallib->get_files_info(null, $filesAdded);
        $removedFileInfos = empty($filesRemoved) ? [] : $filegallib->get_files_info(null, $filesRemoved);

        $result = '<table class="table"><tr><td class="diffdeleted diffprefix">-</td><td class="diffdeleted diffcontent"><del class="diffchar deleted">';

        foreach ($removedFileInfos as $file) {
            $url = smarty_modifier_sefurl($file['fileId'], 'file');
            $result .= smarty_modifier_iconify($url, $file['filetype'], $file['fileId'], 1);
            $result .= ' <a href="' . $url . '">' . smarty_modifier_escape($file['name']) . '</a><br>';
        }

        $result .= '</del></td><td class="diffadded diffprefix">+</td><td class="diffadded diffcontent"><ins class="diffchar inserted">';

        foreach ($addedFileInfos as $file) {
            $url = smarty_modifier_sefurl($file['fileId'], 'file');
            $result .= smarty_modifier_iconify($url, $file['filetype'], $file['fileId'], 1);
            $result .= ' <a href="' . $url . '">' . smarty_modifier_escape($file['name']) . '</a>';
            $result .= ' <a href="' . $url . '&amp;display" target="_blank" class="tips cboxElement" title="Preview" data-bs-content="' . smarty_modifier_escape($file['name']) . '" data-box="box-3" data-bs-original-title="Preview">';
            $result .= '<span class="icon icon-view fas fa-search-plus"></span></a><br/>';
        }

        $result .= '</ins></td></tr></table>';

        return $result;
    }

    public function filterFile($info)
    {
        $filter = $this->getOption('filter');

        if (! $filter) {
            return true;
        }

        $parts = explode('*', $filter);
        $parts = array_map('preg_quote', $parts, array_fill(0, count($parts), '/'));

        $body = implode('[\w-]*', $parts);

        // Force begin, ignore end which may contain charsets or other attributes
        return preg_match("/^$body/", $info['filetype']);
    }

    private function getFileInfo($ids)
    {
        $db = TikiDb::get();
        $table = $db->table('tiki_files');

        $sortOrder = $this->getOption('displayOrder');

        $data = $table->fetchAll(
            [
                'fileId',
                'name',
                'filename',
                'filetype',
                'archiveId',
                'lastModif',
                'description'
            ],
            [
                'fileId' => $table->in($ids),
            ],
            -1,
            -1,
            $table->sortMode($sortOrder)
        );

        $out = [];
        foreach ($data as $info) {
            $out[$info['fileId']] = $info;
        }

        if (! $sortOrder) { // re-order result into order they were attached
            $out2 = [];
            foreach ($ids as $id) {
                if (isset($out[$id])) {
                    $out2["$id"] = $out[$id];
                } else {
                    $itemId = $this->getItemId();
                    if ($itemId) {
                        $smarty = TikiLib::lib('smarty');

                        Feedback::warning(
                            tr(
                                'File #%0 missing (was attached to trackerfield "%1" on item %2)',
                                $id,
                                $this->getConfiguration('permName'),
                                smarty_function_object_link(
                                    [
                                        'id'   => $itemId,
                                        'type' => 'trackeritem',
                                    ],
                                    $smarty->getEmptyInternalTemplate()
                                )
                            )
                        );
                    }
                }
            }
            $out = $out2;
        } elseif (str_contains($sortOrder, 'name')) {
            $sep = strrpos($sortOrder, '_');
            $field = substr($sortOrder, 0, $sep);
            $dir = substr($sortOrder, $sep + 1);
            $sortArray = array_map(function ($file) use ($field) {
                return $file[$field] ?? '';
            }, $out);
            natsort($sortArray);
            if ($dir == 'desc') {
                $sortArray = array_reverse($sortArray, true);
            }
            $sorted = [];
            foreach ($sortArray as $key => $_) {
                $sorted[$key] = $out[$key];
            }
            $out = $sorted;
        }

        return $out;
    }

    private function handleUpload($galleryId, $file)
    {
        if (empty($file['tmp_name'])) {
            // Not an actual file upload attempt, just skip
            return false;
        }

        if (! is_uploaded_file($file['tmp_name'])) {
            Feedback::error(tr('Problem with uploaded file: "%0"', $file['name']));
            return false;
        }

        $filegallib = TikiLib::lib('filegal');
        $gal_info = $filegallib->get_file_gallery_info($galleryId);

        if (! $gal_info) {
            Feedback::error(tr('No gallery for uploaded file, galleryId=%0', $galleryId));
            return false;
        }

        $perms = Perms::get('file gallery', $galleryId);
        if (! $perms->upload_files) {
            Feedback::error(tr('You don\'t have permission to upload a file to gallery "%0"', $gal_info['name']));
            return false;
        }

        $fileIds = $this->getConfiguration('files');

        if ($this->getOption('displayMode') == 'img' && is_array($fileIds) && count($fileIds) > 0) {
            return $filegallib->update_single_file($gal_info, $file['name'], $file['size'], $file['type'], file_get_contents($file['tmp_name']), $fileIds[0]);
        } else {
            return $filegallib->upload_single_file($gal_info, $file['name'], $file['size'], $file['type'], file_get_contents($file['tmp_name']));
        }
    }

    public function getDocumentPart(Search_Type_Factory_Interface $typeFactory)
    {
        $value = $this->getValue();
        $baseKey = $this->getBaseKey();

        if ($this->getOption('indexGeometry') && $this->getValue()) {
            $urls = [];

            foreach (explode(',', $this->getValue()) as $value) {
                $urls[] = smarty_modifier_sefurl($value, 'file');
            }
            return [
                'geo_located' => $typeFactory->identifier('y'),
                'geo_file' => $typeFactory->identifier(implode(',', $urls)),
                'geo_file_format' => $typeFactory->identifier($this->getOption('indexGeometry')),
                $baseKey => $typeFactory->identifier($value),
            ];
        } else {
            $fileIds = array_filter(explode(',', $value));
            $fileInfo = $this->getFileInfo($fileIds);

            $names = [];
            $fileNames = [];
            $fileTypes = [];
            foreach ($fileInfo as $info) {
                $names[] = $info['name'];
                $fileNames[] = $info['filename'];
                $fileTypes[] = $info['filetype'];
            }

            $out = [
                $baseKey => $typeFactory->sortable($value),
                "{$baseKey}_names" => $typeFactory->multivalue($names),
                "{$baseKey}_filenames" => $typeFactory->multivalue($fileNames),
                "{$baseKey}_filetypes" => $typeFactory->multivalue($fileTypes),
                "{$baseKey}_text" => $typeFactory->sortable(implode(" ", $names)),
            ];
            return $out;
        }
    }

    public function getProvidedFields(): array
    {
        $baseKey = $this->getBaseKey();
        if ($this->getOption('indexGeometry') && $this->getValue()) {
            return ['geo_located', 'geo_file', 'geo_file_format', $baseKey];
        } else {
            $fields = [
                $baseKey,
                "{$baseKey}_text",
                "{$baseKey}_names",
                "{$baseKey}_filenames",
                "{$baseKey}_filetypes",
            ];
            return $fields;
        }
    }

    public function getProvidedFieldTypes(): array
    {
        $baseKey = $this->getBaseKey();
        if ($this->getOption('indexGeometry') && $this->getValue()) {
            return [
                'geo_located' => 'identifier',
                'geo_file' => 'identifier',
                'geo_file_format' => 'identifier',
                $baseKey => 'identifier'
            ];
        } else {
            $baseKey = $this->getBaseKey();
            return [
                $baseKey => 'sortable',
                "{$baseKey}_text" => 'sortable',
                "{$baseKey}_names" => 'multivalue',
                "{$baseKey}_filenames" => 'multivalue',
                "{$baseKey}_filetypes" => 'multivalue',
            ];
        }
    }

    public function getGlobalFields(): array
    {
        if ($this->getOption('indexGeometry') && $this->getValue()) {
            return [];
        } else {
            $baseKey = $this->getBaseKey();
            return ["{$baseKey}_text" => true];
        }
    }

    public function getTabularSchema()
    {
        $schema = new Tracker\Tabular\Schema($this->getTrackerDefinition());

        $permName = $this->getConfiguration('permName');
        $name = $this->getConfiguration('name');
        $galleryId = (int) $this->getOption('galleryId');

        $schema->addNew($permName, 'default')
            ->setLabel($name)
            ->setRenderTransform(function ($value) {
                return $value;
            })
            ->setParseIntoTransform(function (&$info, $value) use ($permName) {
                $info['fields'][$permName] = $value;
            });

        $schema->addNew($permName, 'url')
            ->setLabel($name)
            ->setRenderTransform(function ($value) {
                global $base_url;

                $urls = [];
                $fileIds = explode(',', $value);
                foreach ($fileIds as $fileId) {
                    if (is_numeric($fileId)) {
                        $urls[] = $base_url . smarty_modifier_sefurl($fileId, 'display');
                    }
                }

                return implode("\n", $urls);
            })
            ->setParseIntoTransform(function (&$info, $value) use ($permName, $galleryId) {
                global $base_url;
                $filegallib = TikiLib::lib('filegal');

                $gal_info = $filegallib->get_file_gallery($galleryId ? $galleryId : 1);
                if (! $gal_info) {
                    throw new Exception(tr('No gallery to uploaded file to: %0', $galleryId));
                }
                $perms = Perms::get('file gallery', $galleryId);
                if (! $perms->upload_files) {
                    throw new Exception(tr('You don\'t have permission to upload a file to gallery "%0"', $gal_info['name']));
                }

                $fileIds = [];
                $urls = explode("\n", $value);
                foreach ($urls as $url) {
                    if (str_starts_with($url, $base_url) && preg_match('/(\d+)$/', $url, $m)) {
                        $fileIds[] = $m[1];
                    } else {
                        $file_info = $filegallib->get_info_from_url($url);
                        if ($file_info) {
                            $fileIds[] = $filegallib->upload_single_file($gal_info, $file_info['name'], $file_info['size'], $file_info['type'], $file_info['data']);
                        }
                    }
                }
                $info['fields'][$permName] = implode(',', $fileIds);
            });

        return $schema;
    }
}
