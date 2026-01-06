<?php

declare(strict_types=1);

namespace Extcode\CartEvents\Controller;

/*
 * This file is part of the package extcode/cart-events.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */
use Extcode\CartEvents\Domain\Repository\EventDateRepository;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

class EventDateController extends ActionController
{
    public function __construct(
        private readonly EventDateRepository $eventDateRepository,
    ) {}

    protected function initializeAction(): void
    {
        if (!empty($GLOBALS['TSFE']) && is_object($GLOBALS['TSFE'])) {
            static $cacheTagsSet = false;

            if (!$cacheTagsSet) {
                $GLOBALS['TSFE']->addCacheTags(['tx_cartevents']);
                $cacheTagsSet = true;
            }
        }
    }

    public function listAction(): ResponseInterface
    {
        if (!$this->settings) {
            $this->settings = [];
        }

        $limit = (int)$this->settings['limit'] ?: (int)$this->configurationManager->getConfiguration(
            ConfigurationManagerInterface::CONFIGURATION_TYPE_SETTINGS,
            'CartEvents'
        )['view']['list']['limit'];

        $pidList = $this->configurationManager->getConfiguration(
            ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK
        )['persistence']['storagePid'];

        $eventDates = $this->eventDateRepository->findNext($limit, $pidList)->fetchAll();

        $this->view->assign('eventDates', $eventDates);

        $this->addCacheTags($eventDates);
        return $this->htmlResponse();
    }

    protected function addCacheTags(iterable $eventDates): void
    {
        $cacheTags = [];

        if (!empty($GLOBALS['TSFE']) && is_object($GLOBALS['TSFE'])) {
            foreach ($eventDates as $eventDate) {
                $eventId = null;

                if (is_array($eventDate)) {
                    if (isset($eventDate['event_uid'])) {
                        $eventId = (int)$eventDate['event_uid'];
                    } elseif (isset($eventDate['event'])) {
                        $eventId = (int)$eventDate['event'];
                    }
                } elseif (is_object($eventDate)) {
                    if (method_exists($eventDate, 'getEvent')) {
                        $event = $eventDate->getEvent();
                        if (is_object($event) && method_exists($event, 'getUid')) {
                            $eventId = (int)$event->getUid();
                        } elseif (is_int($event)) {
                            $eventId = $event;
                        }
                    }
                }

                if ($eventId) {
                    $cacheTags[] = 'tx_cartevents_event_' . $eventId;
                }
            }

            if (count($cacheTags) > 0) {
                $GLOBALS['TSFE']->addCacheTags($cacheTags);
            }
        }
    }
}
