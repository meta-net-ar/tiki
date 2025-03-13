<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function wikiplugin_ftp_info()
{
    return [
        'name' => tra('FTP'),
        'documentation' => 'PluginFTP',
        'description' => tra('Create a button for downloading a file from an FTP server'),
        'prefs' => [ 'wikiplugin_ftp' ],
        'validate' => 'all',
        'body' => tra('File name on the server'),
        'iconname' => 'upload',
        'introduced' => 3,
        'params' => [
            'server' => [
                'required' => true,
                'name' => tra('Server Name'),
                'description' => tra('Name of the server for the FTP account. Example: ')
                    . '<code>ftp.myserver.com</code>',
                'since' => '3.0',
                'filter' => 'text',
                'default' => ''
            ],
            'user' => [
                'required' => true,
                'name' => tra('Username'),
                'description' => tra('Username for the FTP account'),
                'since' => '3.0',
                'filter' => 'username',
                'default' => ''
            ],
            'password' => [
                'required' => true,
                'name' => tra('Password'),
                'description' => tra('Password for the FTP account'),
                'since' => '3.0',
                'filter' => 'text',
                'default' => ''
            ],
            'title' => [
                'required' => false,
                'name' => tra('Download Button Label'),
                'description' => tra('Label for the FTP download button'),
                'since' => '3.0',
                'filter' => 'text',
                'default' => ''
            ],
            'ftpMode' => [
                'required' => false,
                'name' => tra('FTP mode'),
                'description' => tra('Defines how data connections are initiated. Please use the passive mode to avoid data connections errors in case the client is behind firewall.'),
                'since' => '26.0',
                'filter' => 'string',
                'default' => 'passive',
                'options' => [
                    ['text' => tra('Passive'), 'value' => 'passive'],
                    ['text' => tra('Active'), 'value' => 'active']
                ]
            ]
        ],
    ];
}

function wikiplugin_ftp($data, $params)
{
    extract($params, EXTR_SKIP);
    if (empty($server) || empty($user) || empty($password)) {
        return tra('missing parameters');
    }

    $smarty = TikiLib::lib('smarty');

    if (! empty($_REQUEST['ftp_download']) && $_REQUEST['file'] == $data) {
        if (! ($conn_id = ftp_connect($server))) {
            ftp_close($conn_id);
            return tra('Connection failed');
        }
        if (! ($login_result = ftp_login($conn_id, $user, $password))) {
            ftp_close($conn_id);
            return tra('Incorrect param');
        }

        ftp_pasv($conn_id, ($ftpMode == 'passive') ? true : false);

        // Check if the file exists on the FTP server before processing the download to avaoid errors due to missing files
        if (ftp_size($conn_id, $data) === -1) {
            Feedback::errorAndDie(tra("The file you are trying to download was not found on the server or you may not have permissions to access it!"), \Laminas\Http\Response::STATUS_CODE_404);
        }

        $local = "temp/$data";
        if (! ftp_get($conn_id, $local, $data, FTP_BINARY)) {
            ftp_close($conn_id);
            return tra('failed');
        }
        ftp_close($conn_id);
        $content = file_get_contents($local);
        $type = filetype($local);
        unlink($local);
        header("Content-type: $type");
        header("Content-Disposition: attachment; filename=\"$data\"");
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        echo "$content";
        die;
    } else {
        if (isset($title)) {
            $smarty->assign('ftptitle', $title);
        }
        $smarty->assign_by_ref('file', $data);
        return $smarty->fetch('wiki-plugins/wikiplugin_ftp.tpl');
    }
}
