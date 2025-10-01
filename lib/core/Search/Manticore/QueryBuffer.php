<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Search\Manticore;

use Exception;

class QueryBuffer
{
    private $client;
    private $count;
    private $prefix;
    private $buffer = [];
    private $bufferLength = 0;
    private $max_allowed_packet;

    public function __construct(PdoClient $client, $count, $prefix, $max_allowed_packet)
    {
        $this->client = $client;
        $this->count = max(10, (int) $count);
        $this->prefix = $prefix;
        $this->max_allowed_packet = $max_allowed_packet;
    }

    public function push($block)
    {
        $length = strlen($block);

        if ($this->bufferLength + $length >= $this->max_allowed_packet) {
            $this->flush();
        }

        $this->buffer[] = $block;
        $this->bufferLength += $length;

        if (count($this->buffer) == $this->count) {
            $this->flush();
        }
    }

    public function flush()
    {
        if (count($this->buffer) == 0) {
            return;
        }

        $this->realFlush();
    }

    public function setPrefix($prefix)
    {
        if ($prefix !== $this->prefix) {
            $this->flush();
        }

        $this->prefix = $prefix;
    }

    private function realFlush()
    {
        $query = $this->prefix . implode(', ', $this->buffer);
        try {
            $this->client->prepareAndExecuteWithRetry($query);
        } catch (Exception $e) {
            // one failing shouldn't prevent the whole batch from processing, try to execute individually
            if (count($this->buffer) > 1) {
                foreach ($this->buffer as $row) {
                    $query = $this->prefix . $row;
                    try {
                        $this->client->prepareAndExecuteWithRetry($query);
                    } catch (Exception $e) {
                        // ignore individual ones, raise the first one
                    }
                }
            }
            throw $e;
        }
        $this->clear();
    }

    public function clear()
    {
        $this->buffer = [];
        $this->bufferLength = 0;
    }
}
