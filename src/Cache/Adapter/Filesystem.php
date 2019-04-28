<?php

namespace Utopia\Cache;

class Filesystem implements Adapter
{
    /**
     * @var string
     */
    protected $path = '';

    /**
     * Filesystem constructor.
     * @param string $path
     */
    public function __construct($path)
    {
        $this->path = $path;
    }

    /**
     * @param string $key
     * @param int $ttl time in seconds
     * @param mixed $data
     * @return array
     * @throws \Exception
     */
    public function load($key, $ttl, $data = [])
    {
        $file = $this->getPath($key);

        if (file_exists($file) && (filemtime($file) + $ttl > time())) { // Cache is valid
            return json_decode(file_get_contents($file), true);
        }

        if(is_callable($data)) {
            $data = call_user_func($data);
            $this->save($key, $data);
        }

        return $data;
    }

    /**
     * @param string $key
     * @param string $data
     * @return string
     * @throws \Exception
     */
    public function save($key, $data)
    {
        if(empty($data)) {
            return '';
        }

        $file = $this->getPath($key);

        if (!file_exists(dirname($file))) { // Checks if directory path to file exists
            if(!@mkdir(dirname($file), 0755, true)) {
                if (!file_exists(dirname($file))) { // Checks race condition for mkdir function
                    throw new \Exception('Can\'t create directory ' . dirname($file));
                }
            }
        }

        return file_put_contents($file, $data, LOCK_EX);
    }

    /**
     * @param string $filename
     * @return string
     */
    public function getPath($filename)
    {
        $path = '';

        for ($i = 0; $i < 4; $i++) {
            $path = ($i < strlen($filename)) ? $path . DIRECTORY_SEPARATOR . $filename[$i] : $path . DIRECTORY_SEPARATOR . 'x';
        }

        return $this->path . $path . DIRECTORY_SEPARATOR . $filename;
    }
}
