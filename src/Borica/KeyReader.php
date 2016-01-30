<?php

namespace Mirovit\Borica;

trait KeyReader
{
    /**
     * Read a key / certificate file and return its contents.
     *
     * @param $key
     * @return string
     */
    public function readKey($key)
    {
        $fp = fopen($key, 'r');
        $read = fread($fp, 8192);
        fclose($fp);

        return $read;
    }
}