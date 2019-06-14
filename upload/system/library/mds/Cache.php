<?php

namespace Mds;

class Cache
{
    private $cache_dir;
    private $cache;

    public function __construct($cache_dir = null)
    {
        $this->cache_dir = $cache_dir;
    }

    /**
     * @param $name
     *
     * @return bool
     */
    public function has($name)
    {
        $cache = $this->load($name);

        return is_array($cache) && ($cache['valid'] - 30) > time();
    }

    /**
     * @param $name
     *
     * @return mixed
     */
    protected function load($name)
    {
        if (!isset($this->cache[$name])) {
            if (file_exists($this->cache_dir . $name) && $content = file_get_contents($this->cache_dir . $name)) {
                $this->cache[$name] = json_decode($content, true);

                return $this->cache[$name];
            }

            $this->create_dir($this->cache_dir);
        } else {
            return $this->cache[$name];
        }
    }

    protected function create_dir($dir_array)
    {
        /*if (!is_array($dir_array)) {
            $dir_array = explode('/', $this->cache_dir);
        }
        array_pop($dir_array);
        $dir = implode('/', $dir_array);

        if ($dir != '' && !is_dir($dir)) {
            $this->create_dir($dir_array);
            if (!mkdir($dir) && !is_dir($dir)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $dir));
            }
        }*/
    }

    /**
     * @param $name
     *
     * @return mixed|null
     */
    public function get($name)
    {
        $cache = $this->load($name);
        if (is_array($cache) && $cache['valid'] > time()) {
            return $cache['value'];
        }

        return null;
    }

    /**
     * @param     $name
     * @param     $value
     * @param int $time
     *
     * @return bool
     */

    public function put($name, $value, $time = 1440)
    {
        $cache = json_encode(['value' => $value, 'valid' => time() + ($time * 60)]);
        if (file_put_contents($this->cache_dir . $name, $cache)) {
            $this->cache[$name] = $cache;

            return true;
        }

        return false;
    }

    /**
     * @param $name
     *
     * @return bool
     */
    public function forget($name)
    {
        $cache = json_encode(['value' => '', 'valid' => 0]);
        if (file_put_contents($this->cache_dir . $name, $cache)) {
            $this->cache[$name] = $cache;

            return true;
        }

        return false;
    }
}