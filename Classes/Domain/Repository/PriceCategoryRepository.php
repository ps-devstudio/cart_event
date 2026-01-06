<?php

namespace Extcode\CartEvents\Domain\Repository;

/*
 * This file is part of the package extcode/cart-events.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

use TYPO3\CMS\Extbase\Persistence\Repository;

use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\QueryResult;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

class PriceCategoryRepository extends Repository
{
	/**
	 * Find price categories for an eventDate uid ignoring storagePid restrictions.
	 *
	 * @param int $eventDateUid
	 * @return QueryResult
	 */
	public function findByEventDateUidIgnoreStorage(int $eventDateUid)
	{
		$query = $this->createQuery();
		$query->getQuerySettings()->setRespectStoragePage(false);
		$query->matching($query->equals('eventDate', $eventDateUid));
		return $query->execute();
	}
}
