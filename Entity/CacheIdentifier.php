<?php

namespace Clownfish\Bundle\CacheMgrBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Eventus\MainBundle\Model\CacheGeocode as BaseEntity;


/**
 * CacheIdentifier
 */
class CacheIdentifier extends BaseEntity
{

    /**
     * @var string
     */
    private $identifier;

	/**
	 * @var string
	 */
	private $label;

	/**
     * @var string
     */
    private $tags;

    /**
     * @var integer
     */
    private $timestamp;

    /**
     * @var integer
     */
    private $lifetime;

	/**
	 * @var string
	 */
	private $backend;

	/**
     * Set identifier
     *
     * @param string $identifier
     *
     * @return CacheIdentifier
     */
    public function setIdentifier($identifier)
    {
        $this->identifier = $identifier;

        return $this;
    }

    /**
     * Get identifier
     *
     * @return string
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

	/**
	 * Set label
	 *
	 * @param string $label
	 *
	 * @return CacheIdentifier
	 */
	public function setLabel($label)
	{
		$this->label = $label;

		return $this;
	}

	/**
	 * Get label
	 *
	 * @return string
	 */
	public function getLabel()
	{
		return $this->label;
	}

    /**
     * Set tags
     *
     * @param string $tags
     *
     * @return CacheIdentifier
     */
    public function setTags($tags)
    {
        $this->tags = $tags;

        return $this;
    }

    /**
     * Get tags
     *
     * @return string
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * Set timestamp
     *
     * @param integer $timestamp
     *
     * @return CacheIdentifier
     */
    public function setTimestamp($timestamp)
    {
        $this->timestamp = $timestamp;

        return $this;
    }

    /**
     * Get timestamp
     *
     * @return integer
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }

    /**
     * Set lifetime
     *
     * @param integer $lifetime
     *
     * @return CacheIdentifier
     */
    public function setLifetime($lifetime)
    {
        $this->lifetime = $lifetime;

        return $this;
    }

    /**
     * Get lifetime
     *
     * @return integer
     */
    public function getLifetime()
    {
        return $this->lifetime;
    }

    /**
     * Set backend
     *
     * @param string $backend
     *
     * @return CacheIdentifier
     */
    public function setBackend($backend)
    {
        $this->backend = $backend;

        return $this;
    }

    /**
     * Get backend
     *
     * @return string
     */
    public function getBackend()
    {
        return $this->backend;
    }
}
