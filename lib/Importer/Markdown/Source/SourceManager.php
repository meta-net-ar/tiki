<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Lib\Importer\Markdown\Source;

class SourceManager
{
    private string $error = '';

    public function getError(): string
    {
        return $this->error;
    }

    /**
     * @param array $cfg        ['type' => 'local'|'git', 'local_path'? , 'repo_url'? , 'git_pull'?]
     * @param ?string $uploadTmp
     * @param ?string $uploadName
     */
    public function fetchToTempZip(array $cfg, ?string $uploadTmp = null, ?string $uploadName = null): ?string
    {
        $this->error = '';
        try {
            $type = (string)($cfg['type'] ?? 'local');

            if ($type === 'local') {
                // Priorité à l’upload si fourni, sinon chemin local
                if ($uploadTmp) {
                    $prov = new FilesystemProvider('upload', $cfg, [
                        'uploadedPath' => $uploadTmp,
                        'uploadedName' => $uploadName,
                    ]);
                    return $prov->fetchToTempZip();
                }
                $prov = new FilesystemProvider('path', $cfg, []);
                return $prov->fetchToTempZip();
            }

            if ($type === 'git') {
                $prov = new GitWorkingCopyProvider([
                    'repo_url'      => $cfg['repo_url'] ?? '',
                    'repo_branch'   => $cfg['repo_branch'] ?? 'main',
                    'git_pull'      => ! empty($cfg['git_pull']),
                    'repo_token'  => $cfg['repo_token'] ?? null,   // optionnel
                ], [
                    'timeout'       => (int)($cfg['git_timeout'] ?? 45),
                ]);
                return $prov->fetchToTempZip();
            }

            throw new \RuntimeException('Unsupported source type: ' . $type);
        } catch (\Throwable $e) {
            $this->error = $e->getMessage();
            return null;
        }
    }
}
