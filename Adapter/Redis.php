<?php

namespace Clownfish\Bundle\CacheMgrBundle\Adapter;

/**
 * An adapter for the direct redis cache access in
 * PHP classes, allows globaly disabling of redis cacheing
 * for developing purposes
 * Created by IntelliJ IDEA.
 * User: artur
 * Date: 09.10.16
 * Time: 16:49
 */
class Redis
{

    /**
     * @var \Predis\Client
     */
    protected $redisClient = null;

    protected $kernel;

    /**
     * Redis constructor.
     * @param \Predis\Client $redisClient
     */
    public function __construct(\Predis\Client $redisClient, $kernel) {
        $this->redisClient = $redisClient;
        $this->kernel = $kernel;
    }

    /**
     * @param $cacheKey
     * @param $value
     */
    public function set($cacheKey, $value) {
        $this->redisClient->set($cacheKey, $value);
    }

    /**
     * @param $cacheKey
     * @return string
     */
    public function get($cacheKey) {
        return $this->redisClient->get($cacheKey);
    }

    /**
     * @param $cacheKey
     * @param $expirationAge
     */
    public function expire($cacheKey, $expirationAge) {
        if (in_array($this->kernel->getEnvironment(), array('dev', 'test'))) {
            // set the value very low to be able to check the effect in DEV/TEST environment but
            // on the other hand to invalidate the cache quickly
            $this->redisClient->expire($cacheKey, $expirationAge = 10);
        } else {
            $this->redisClient->expire($cacheKey, $expirationAge);
        }
    }

}