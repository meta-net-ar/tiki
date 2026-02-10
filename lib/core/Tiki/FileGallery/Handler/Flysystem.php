<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\FileGallery\Handler;

use League\Flysystem\AwsS3V3\AwsS3V3Adapter;
use League\Flysystem\AwsS3V3\PortableVisibilityConverter as AwsPortableVisibilityConverter;
use League\Flysystem\Filesystem;
use League\Flysystem\Ftp\FtpAdapter;
use League\Flysystem\Ftp\FtpConnectionOptions;
use League\Flysystem\Local\LocalFilesystemAdapter;
use League\Flysystem\PhpseclibV3\SftpConnectionProvider;
use League\Flysystem\PhpseclibV3\SftpAdapter;
use League\Flysystem\UnixVisibility\PortableVisibilityConverter as UnixPortableVisibilityConverter;
use League\Flysystem\Visibility;
use League\Flysystem\WebDAV\WebDAVAdapter;
use Sabre\DAV\Client as DavClient;
use Tiki\FileGallery\FileWrapper\FlysystemFile;

class Flysystem implements HandlerInterface
{
    protected $adapter;
    protected $filesystem;
    protected $path;

    public function __construct($config, $path)
    {
        global $prefs;

        if (is_string($config)) {
            $config = json_decode($config, true);
        } elseif (is_object($config)) {
            $config = (array)$config;
        }
        $this->adapter = $this->initAdapter($config);
        $this->filesystem = new Filesystem($this->adapter);
        $this->path = $path;
    }

    public function getFilesystem()
    {
        return $this->filesystem;
    }

    public function getFileWrapper($file)
    {
        return new FlysystemFile($this->filesystem, $file->path);
    }

    public function delete($file)
    {
        return $this->filesystem->delete($file->path);
    }

    public function uniquePath($file)
    {
        if (! empty($file->path)) {
            return $file->path;
        }

        if ($this->path) {
            $prefix = $this->path . '/';
        } else {
            $prefix = '';
        }

        if ($file->directoryPattern) {
            $prefix .= trim($file->directoryPattern, '/') . '/';
        }

        $counter = 1;
        $result = $prefix . $file->name;
        while ($this->filesystem->fileExists($result)) {
            $result = $prefix . preg_replace('/^(\.*[^\.]*)(\.)?/', "\$1_{$counter}\$2", $file->name);
            $counter++;
        }

        return $result;
    }

    public function isWritable()
    {
        return true;
    }

    protected function initAdapter($config)
    {
        switch ($config['adapter']) {
            case 'local':
            case 'ftp':
            case 'sftp':
            case 's3':
            case 'webdav':
                $method = 'init' . ucfirst($config['adapter']);
                return $this->$method($config);
            default:
                throw new AdapterNotFoundException(tr('Flysystem adapter not implemented: %0', $config['adapter']));
        }
    }

    protected function initLocal($config)
    {
        return new LocalFilesystemAdapter(
            // Determine the root directory
            $config['path'],
            // Customize how visibility is converted to unix permissions
            UnixPortableVisibilityConverter::fromArray([
                'file' => [
                    'public' => 0644,
                    'private' => 0600,
                ],
                'dir' => [
                    'public' => 0755,
                    'private' => 0700,
                ],
            ]),
            // Write flags
            LOCK_EX,
            // How to deal with links, either DISALLOW_LINKS or SKIP_LINKS
            LocalFilesystemAdapter::SKIP_LINKS,
            // use default mime type detector
            null,
            // no lazy root creation
            false,
            // use inconclusive mime type fallback to prevent null values
            true,
        );
    }

    protected function initFtp($config)
    {
        $ftp_config = array_intersect_key($config, array_flip(['host', 'root_path', 'username', 'password', 'port', 'ssl', 'timeout', 'utf8', 'passive']));
        $ftp_config['root'] = $ftp_config['root_path'] ?? '';
        $ftp_config['ssl'] = (bool)$ftp_config['ssl'];
        $ftp_config['utf8'] = (bool)$ftp_config['utf8'];
        $ftp_config['passive'] = (bool)$ftp_config['passive'];
        return new FtpAdapter(
            FtpConnectionOptions::fromArray($ftp_config)
        );
    }

    protected function initSftp($config)
    {
        return new SftpAdapter(
            new SftpConnectionProvider(
                $config['host'],
                $config['username'],
                $config['password'],
                $config['private_key'],
                $config['private_key_password'],
                $config['port'],
                false,
                $config['timeout']
            ),
            $config['root_path'], // root path (required)
            UnixPortableVisibilityConverter::fromArray([
                'file' => [
                    'public' => 0644,
                    'private' => 0600,
                ],
                'dir' => [
                    'public' => 0755,
                    'private' => 0700,
                ],
            ])
        );
    }

    protected function initS3($config)
    {
        if (! class_exists('\Aws\S3\S3Client')) {
            throw new \Exception(tr('AWS SDK package should be installed before using S3 direct mapped directories.'));
        }
        $client = new \Aws\S3\S3Client([
            'version' => 'latest',
            'region' => $config['region'],
            'credentials' => [
                'key' => $config['key'],
                'secret' => $config['secret'],
            ],
        ]);
        return new AwsS3V3Adapter(
            $client,
            $config['bucket'],
            $config['path_prefix'],
            new AwsPortableVisibilityConverter(
                // Optional default for directories
                Visibility::PUBLIC // or ::PRIVATE
            )
        );
    }

    protected function initWebdav($config)
    {
        $client = new DavClient([
            'baseUri' => $config['base_uri'],
            'userName' => $config['username'],
            'password' => $config['password']
        ]);
        return new WebDAVAdapter($client);
    }
}
