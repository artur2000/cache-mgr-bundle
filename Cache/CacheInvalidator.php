<?php
/**
 * Created by PhpStorm.
 * User: artur
 * Date: 13.03.16
 * Time: 01:32
 */

namespace Clownfish\Bundle\CacheMgrBundle\Cache;

use Doctrine\ORM\Event\OnFlushEventArgs;
use \Exception;

/**
 * Class CacheInvalidator
 *
 * @package Clownfish\Bundle\CacheMgrBundle\Cache
 * @author Artur Cichosz - <ac@clownfishweb.pl>
 */
class CacheInvalidator
{

	/**
	 */
	public function __construct()
	{
		$this->cacheIds = array();
	}

	/**
	 * @param OnFlushEventArgs $eventArgs
	 */
	public function onFlush(OnFlushEventArgs $eventArgs)
	{
		$em = $eventArgs->getEntityManager();
		$uow = $em->getUnitOfWork();

		// $uow->getScheduledCollectionDeletions()
		// $uow->getScheduledCollectionUpdates()

		$scheduledEntityChanges = array(
			'insert' => $uow->getScheduledEntityInsertions(),
			'update' => $uow->getScheduledEntityUpdates(),
			'delete' => $uow->getScheduledEntityDeletions()
		);

		$cacheIds = array();

		foreach ($scheduledEntityChanges as $change => $entities) {
			foreach ($entities as $entity) {
				$cacheIds = array_merge($cacheIds, $this->getCacheIdsForEntity($entity, $change));
			}
		}

		if (count($cacheIds) == 0) {
			return;
		}

		$cacheIds = array_unique($cacheIds);

		$cacheImpl = $em->getConfiguration()->getQueryCacheImpl();
		foreach ($cacheIds as $identifier) {
			$cacheImpl->delete($identifier);
		}

	}

	/**
	 * @param $entity
	 * @param $change
	 * @return array
	 */
	protected function getCacheIdsForEntity($entity, $change)
	{
		$parsedCacheIds = array();
		return $parsedCacheIds;
	}

}
