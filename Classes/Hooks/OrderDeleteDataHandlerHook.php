<?php

declare(strict_types=1);

namespace Extcode\CartEvents\Hooks;

/*
 * This file is part of the package extcode/cart-events.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class OrderDeleteDataHandlerHook
{
    public function processCmdmap_deleteAction(
        string $table,
        int $id,
        array $recordToDelete,
        bool &$recordWasDeleted,
        DataHandler $dataHandler
    ): void {
        if ($table !== 'tx_cart_domain_model_order_item' || $recordWasDeleted) {
            return;
        }

        if ((int)($recordToDelete['deleted'] ?? 0) === 1 || $this->orderHasTicket($recordToDelete)) {
            return;
        }

        $eventUidsToFlush = [];

        foreach ($this->getOrderProducts($id) as $orderProduct) {
            if (($orderProduct['product_type'] ?? '') !== 'CartEvents') {
                continue;
            }

            $reservation = $this->resolveReservation($orderProduct);
            if ($reservation === null || !(bool)$reservation['handle_seats']) {
                continue;
            }

            $quantity = max(0, (int)($orderProduct['count'] ?? 0));
            if ($quantity === 0) {
                continue;
            }

            if ((bool)$reservation['handle_seats_in_price_category'] && (int)$reservation['price_category_uid'] > 0) {
                $this->decreaseSeatsTaken(
                    'tx_cartevents_domain_model_pricecategory',
                    (int)$reservation['price_category_uid'],
                    $quantity
                );
            } else {
                $this->decreaseSeatsTaken(
                    'tx_cartevents_domain_model_eventdate',
                    (int)$reservation['event_date_uid'],
                    $quantity
                );
            }

            $eventUidsToFlush[(int)$reservation['event_uid']] = (int)$reservation['event_uid'];
        }

        $this->flushEventCaches(array_values($eventUidsToFlush));
    }

    protected function orderHasTicket(array $orderItem): bool
    {
        return trim((string)($orderItem['ticket_number'] ?? '')) !== ''
            || (int)($orderItem['ticket_pdfs'] ?? 0) > 0
            || (int)($orderItem['ticket_date'] ?? 0) > 0;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function getOrderProducts(int $orderItemUid): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_cart_domain_model_order_product');

        $queryBuilder->getRestrictions()->removeAll();

        return $queryBuilder
            ->select('uid', 'product_id', 'product_type', 'sku', 'title', 'count')
            ->from('tx_cart_domain_model_order_product')
            ->where(
                $queryBuilder->expr()->eq(
                    'item',
                    $queryBuilder->createNamedParameter($orderItemUid, Connection::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    'deleted',
                    $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)
                )
            )
            ->executeQuery()
            ->fetchAllAssociative();
    }

    /**
     * @param array<string, mixed> $orderProduct
     * @return array<string, mixed>|null
     */
    protected function resolveReservation(array $orderProduct): ?array
    {
        $eventDateUid = (int)($orderProduct['product_id'] ?? 0);
        if ($eventDateUid > 0) {
            return $this->getEventDateReservationByUid($eventDateUid);
        }

        return $this->findReservationBySkuAndTitle(
            trim((string)($orderProduct['sku'] ?? '')),
            trim((string)($orderProduct['title'] ?? ''))
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function getEventDateReservationByUid(int $eventDateUid): ?array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_cartevents_domain_model_eventdate');

        $queryBuilder->getRestrictions()->removeAll();

        $row = $queryBuilder
            ->select('uid', 'event', 'handle_seats', 'handle_seats_in_price_category')
            ->from('tx_cartevents_domain_model_eventdate')
            ->where(
                $queryBuilder->expr()->eq(
                    'uid',
                    $queryBuilder->createNamedParameter($eventDateUid, Connection::PARAM_INT)
                )
            )
            ->executeQuery()
            ->fetchAssociative();

        if ($row === false) {
            return null;
        }

        return [
            'event_date_uid' => (int)$row['uid'],
            'event_uid' => (int)$row['event'],
            'price_category_uid' => 0,
            'handle_seats' => (int)$row['handle_seats'] === 1,
            'handle_seats_in_price_category' => (int)$row['handle_seats_in_price_category'] === 1,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function findReservationBySkuAndTitle(string $sku, string $title): ?array
    {
        if ($sku === '' || $title === '') {
            return null;
        }

        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tx_cartevents_domain_model_eventdate');

        $row = $connection->executeQuery(
            'SELECT
                ed.uid AS event_date_uid,
                ed.event AS event_uid,
                ed.handle_seats,
                ed.handle_seats_in_price_category,
                COALESCE(pc.uid, 0) AS price_category_uid
            FROM tx_cartevents_domain_model_eventdate ed
            INNER JOIN tx_cartevents_domain_model_event e
                ON e.uid = ed.event AND e.deleted = 0
            LEFT JOIN tx_cartevents_domain_model_pricecategory pc
                ON pc.event_date = ed.uid AND pc.deleted = 0
            WHERE ed.deleted = 0
              AND (
                    (CONCAT(e.sku, \' - \', ed.sku) = ? AND CONCAT(e.title, \' - \', ed.title) = ?)
                 OR (CONCAT(e.sku, \' - \', ed.sku, \'-\', pc.sku) = ? AND CONCAT(e.title, \' - \', ed.title, \' - \', pc.title) = ?)
              )
            ORDER BY price_category_uid DESC
            LIMIT 1',
            [$sku, $title, $sku, $title],
            [Connection::PARAM_STR, Connection::PARAM_STR, Connection::PARAM_STR, Connection::PARAM_STR]
        )->fetchAssociative();

        if ($row === false) {
            return null;
        }

        return [
            'event_date_uid' => (int)$row['event_date_uid'],
            'event_uid' => (int)$row['event_uid'],
            'price_category_uid' => (int)$row['price_category_uid'],
            'handle_seats' => (int)$row['handle_seats'] === 1,
            'handle_seats_in_price_category' => (int)$row['handle_seats_in_price_category'] === 1,
        ];
    }

    protected function decreaseSeatsTaken(string $table, int $uid, int $quantity): void
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable($table);

        $connection->executeStatement(
            'UPDATE ' . $table . ' SET seats_taken = GREATEST(0, seats_taken - ?) WHERE uid = ?',
            [$quantity, $uid],
            [Connection::PARAM_INT, Connection::PARAM_INT]
        );
    }

    /**
     * @param int[] $eventUids
     */
    protected function flushEventCaches(array $eventUids): void
    {
        if ($eventUids === []) {
            return;
        }

        $cacheManager = GeneralUtility::makeInstance(CacheManager::class);

        foreach ($eventUids as $eventUid) {
            if ($eventUid <= 0) {
                continue;
            }

            $cacheManager->flushCachesInGroupByTag('pages', 'tx_cartevents_event_' . $eventUid);
        }
    }
}
