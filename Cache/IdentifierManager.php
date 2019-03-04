<?php
/**
 * Created by PhpStorm.
 * User: artur
 * Date: 13.03.16
 * Time: 01:32
 */

namespace Clownfish\Bundle\CacheMgrBundle\Cache;

use Clownfish\Bundle\CacheMgrBundle\Entity\CacheIdentifier;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\OnFlushEventArgs;
use \Exception;
use FOS\UserBundle\Model\User;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

/**
 * Class IdentifierManager
 *
 * @package Clownfish\Bundle\CacheMgrBundle\Cache
 * @author Artur Cichosz - <ac@clownfishweb.pl>
 */
class IdentifierManager {

	const ENABLE_QUERY_CACHE = true;

	/**
	 * @var EntityManager|null
	 */
	private $entityManager = null;

	/**
	 * @var TokenStorage|null
	 */
	private $tokenStorage = null;

	/**
	 * @var \Clownfish\Bundle\CacheMgrBundle\Model\CacheIdentifierRepository|null
	 */
	private $repository = null;

	/**
	 * @param EntityManager $entityManager
	 * @param TokenStorage $tokenStorage
	 */
	public function __construct(EntityManager $entityManager, TokenStorage $tokenStorage)
	{
		$this->entityManager = $entityManager;
		$this->tokenStorage = $tokenStorage;
		$this->repository = $this->entityManager->getRepository('ClownfishCacheMgrBundle:CacheIdentifier');
	}

	/**
	 * @param string $backend
	 * @param string $label
	 * @param array $tags
	 * @param int $lifetime
	 * @param null $entity
	 * @throws \Doctrine\DBAL\DBALException
	 * @return string
	 */
	public function registerIdentifier($backend, $label, array $tags, $lifetime = 3600, $entity = null) {

		if (!self::ENABLE_QUERY_CACHE) {
			return null;
		}

		$conn = $this->entityManager->getConnection();

		// generate unified entity tag
		if ($entity) {
			$tags[] = $this->createEntityTag($entity);
		}

		// generate backend tag
		$tags[] = "backend({$backend})";

		// generate identifier for local key register
		$chash = $this->createChash($label, $tags);

		// generate key for redis
		$label = str_replace(':','-',$label);
		$redisKey = $this->createRedisKey($label, $chash);

		/** @var \Clownfish\Bundle\CacheMgrBundle\Entity\CacheIdentifier $existingKey */
		$existingKey = $this->repository->findByChash($chash, $this);
		if ($existingKey) {
			$age = time() - $existingKey->getTimestamp();
			if ( $age <= $existingKey->getLifetime() ) {
				// cache entry still valid - return the redis key
				return $redisKey;
			}
			// cache antry alredy expired so generate a fresh entry
			// the lifetime value is the same in register and in cache backend so we
			// do not need to explicitely invalidate the cache entry
			// the cache backend will handle this already
		}

		$conn->query("REPLACE INTO cache_identifier (identifier, tags, label, timestamp, lifetime, backend) VALUES ('{$chash}', '".implode(',',$tags)."', '".$label."', '".time()."', '{$lifetime}', '{$backend}')");

		return $redisKey;
	}

	/**
	 * @param string $backend
	 * @param string $label
	 * @param array $tags
	 * @param int $lifetime
	 * @param string $entity
	 * @param null $user
	 * @throws Exception
	 * @return string
	 */
	public function registerUserIdentifier($backend, $label, array $tags, $lifetime = 3600, $entity = null, $user = null) {

		if ($user == null) {
			$user = $this->tokenStorage->getToken()->getUser();
		}

		if (!$user) {
			throw new \Exception('A user bound cache identifier can only be registered in context of a valid user session.');
		}

		// generate unified user tag
		$tags[] = $this->createUserTag();

		// register
		return $this->registerIdentifier($backend, $label, $tags, $lifetime, $entity);

	}

	/**
	 * @param string $tag
	 * @return mixed
	 * @throws \Doctrine\DBAL\DBALException
	 */
	public function getByTag($tag) {

		return $this->getByTagsAnd(array($tag));

	}

	/**
	 * Retrive an array with cache identifiers holding all of some specific tag
	 * @param array $tags
	 * @return array
	 * @throws \Doctrine\DBAL\DBALException
	 */
	public function getByTagsAnd(array $tags) {

		$redisKeys = array();
		$results = $this->repository->findByTagsAnd($tags, $this);
		/** @var \Clownfish\Bundle\CacheMgrBundle\Entity\CacheIdentifier $result */
		foreach ($results as $result) {
			$redisKeys[] = $this->createRedisKey(null, null, $result);
		}
		return $redisKeys;

	}

	/**
	 * Retrive an array with cache identifiers holding at least one specific tag
	 * @param array $tags
	 * @return mixed
	 * @throws \Doctrine\DBAL\DBALException
	 */
	public function getByTagsOr(array $tags) {

		$redisKeys = array();
		$results = $this->repository->findByTagsOr($tags, $this);
		/** @var \Clownfish\Bundle\CacheMgrBundle\Entity\CacheIdentifier $result */
		foreach ($results as $result) {
			$redisKeys[] = $this->createRedisKey(null, null, $result);
		}
		return $redisKeys;

	}

	/**
	 * Clear a cache entry by identifier
	 * @param string $identifier
	 * @throws \Doctrine\DBAL\DBALException
	 */
	public function clear($identifier) {

		$resultCache = $this->entityManager->getConfiguration()->getResultCacheImpl();
		$deleted = $resultCache->delete($identifier);
		if ($deleted) {
			$sql = "DELETE FROM cache_identifier WHERE identifier = :identifier";
			$params['identifier'] = $identifier;
			$stmt = $this->entityManager->getConnection()->prepare($sql);
			$stmt->execute($params);
		}

	}

	/**
	 * Clear cache antries by tag
	 * @param $tag
	 */
	public function clearByTag($tag) {

		$identifiers = $this->getByTag($tag);
		foreach ($identifiers as $identifier) {
			$this->clear($identifier);
		}

	}

	/**
	 * Clear cache entries holding all of the provided tags
	 * @param array $tags
	 */
	public function clearByTagsAnd(array $tags) {

		$identifiers = $this->getByTagsAnd($tags);
		foreach ($identifiers as $identifier) {
			$this->clear($identifier);
		}

	}

	/**
	 * Clear cache entries holding at least one of the provided tags
	 * @param array $tags
	 */
	public function clearByTagsOr(array $tags) {

		$identifiers = $this->getByTagsOr($tags);
		foreach ($identifiers as $identifier) {
			$this->clear($identifier);
		}

	}

	/**
	 * Create unified user tag
	 * @param User $user
	 * @return string
	 */
	public function createUserTag(User $user = null) {
		if ($user == null) {
			$user = $this->tokenStorage->getToken()->getUser();
		}
		return 'user-' . $user->getId();
	}

	/**
	 * Create unified entity tag
	 * @param string|Object $entity
	 * @return string
	 */
	public function createEntityTag($entity) {
		if (is_object($entity)) {
			$entity = get_class($entity);
		}
		return 'entity-' . stripslashes(ltrim($entity,'\\'));
	}

	/**
	 * Create unified chash
	 * @param $identifier
	 * @param array $tags
	 * @return string
	 */
	public function createChash($identifier, array $tags = null) {

		if (is_array($tags)) {
			$chash = md5($identifier . ':'. implode(',', $tags));
		} else {
			$chash = md5($identifier);
		}

		return $chash;

	}

	/**
	 * @param null $label
	 * @param null $chash
	 * @param CacheIdentifier $entity
	 * @return null|string
	 * @throws Exception
	 */
	public function createRedisKey($label = null, $chash = null, CacheIdentifier $entity = null) {
		$redisKey = null;
		if ($label && $chash) {
			$redisKey = 'doctrine_query_cache:'.$label.':'.$chash;
		} else if ($entity) {
			$redisKey = 'doctrine_query_cache:'.$entity->getLabel().':'.$entity->getIdentifier();
		} else {
			throw new \Exception('Redis key could not be generated. PLease provide either a key label and key identifier or a CacheIdentifier entity');
		}
		return $redisKey;
	}


}
