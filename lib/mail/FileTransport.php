<?php

namespace Tiki\Lib\mail;

use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;

class FileTransport extends AbstractTransport
{
    private string $filePath;

    /**
     * @param string $filePath  The absolute path to the directory where emails will be saved.
     */
    public function __construct(string $filePath)
    {
        $this->filePath = rtrim($filePath, '/\\');
        parent::__construct();
    }

    /**
     * Performs the actual sending by writing to a file.
     *
     * @param SentMessage $message The SentMessage object, which contains the original RawMessage and Envelope.
     * @return void
     */
    protected function doSend(SentMessage $message): void
    {
        if (! is_dir($this->filePath)) {
            $temp_dir = dirname($this->filePath);

            if (! file_exists($temp_dir)) {
                mkdir($temp_dir, 0775, true);
            }

            $group_write = (bool)((fileperms($temp_dir) & 0x0010));

            mkdir($this->filePath, $group_write ? 0771 : 0751, true);
        }

        $filename = self::generateUniqueFilename() . '.eml';

        $fullPath = $this->filePath . '/' . $filename;

        $content = $message->getOriginalMessage()->toString();

        file_put_contents($fullPath, $content);

        $message->setMessageId(basename($fullPath));
    }

    public function __toString(): string
    {
        return sprintf('file://%s', $this->filePath);
    }

    public static function generateUniqueFilename(): string
    {
        return 'mail_' . date('YmdHis') . '_' . mt_rand();
    }
}
