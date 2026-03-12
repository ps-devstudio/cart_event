<?php

declare(strict_types=1);

namespace Extcode\CartEvents\EventListener\Order\Stock;

/*
 * This file is part of the package extcode/cart-events.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

use Extcode\Cart\Domain\Model\Cart\Product;
use Extcode\CartEvents\Domain\Repository\EventDateRepository;
use Extcode\CartEvents\Domain\Repository\PriceCategoryRepository;
use Extcode\CartPaypal\Event\Order\CancelEvent;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;

class ReleaseStockOnPaypalCancel
{
    public function __construct(
        private readonly PersistenceManager $persistenceManager,
        private readonly EventDateRepository $eventDateRepository,
        private readonly PriceCategoryRepository $priceCategoryRepository,
    ) {}

    public function __invoke(CancelEvent $event): void
    {
        $cartProducts = $event->getCart()->getProducts();

        foreach ($cartProducts as $cartProduct) {
            if ($cartProduct->getProductType() === 'CartEvents') {
                $this->releaseStockForEventDate($cartProduct);
            }
        }

        $this->persistenceManager->persistAll();
    }

    protected function releaseStockForEventDate(Product $cartProduct): void
    {
        $eventDate = $this->eventDateRepository->findByUid($cartProduct->getProductId());
        if (!$eventDate || !$eventDate->isHandleSeats()) {
            return;
        }

        if ($eventDate->isHandleSeatsInPriceCategory()) {
            foreach ($cartProduct->getBeVariants() as $cartBeVariant) {
                $explodedId = explode('-', (string)$cartBeVariant->getId());
                $id = (int)end($explodedId);
                $priceCategory = $this->priceCategoryRepository->findByUid($id);

                if ($priceCategory === null) {
                    continue;
                }

                $newSeatsTaken = max(0, $priceCategory->getSeatsTaken() - $cartBeVariant->getQuantity());
                $priceCategory->setSeatsTaken($newSeatsTaken);
                $this->priceCategoryRepository->update($priceCategory);
            }
            return;
        }

        $newSeatsTaken = max(0, $eventDate->getSeatsTaken() - $cartProduct->getQuantity());
        $eventDate->setSeatsTaken($newSeatsTaken);
        $this->eventDateRepository->update($eventDate);
    }
}
