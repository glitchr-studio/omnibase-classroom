<?php

namespace Base\Classroom\EventSubscriber;

use Base\Classroom\Entity\Product\ResourceOffer;
use Base\Service\DownloadLinks;
use Base\Marketplace\Entity\Order;
use Base\Marketplace\Event\QuickOrderDoneEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

/**
 * The page of a quick order that bought files: the files themselves, each
 * behind a signed download link (a day), once the order is paid. The page
 * is reached by its own signed link - shown after the payment, and mailed
 * (OrderPaidSubscriber) - so the files are for whoever holds it.
 */
final class QuickOrderFilesListener
{
    public function __construct(
        private readonly Environment $twig,
        private readonly DownloadLinks $links,
    ) {
    }

    #[AsEventListener(event: QuickOrderDoneEvent::class)]
    public function __invoke(QuickOrderDoneEvent $event): void
    {
        $resources = self::resourcesOf($event->order);
        if (!$resources) {
            return;
        }

        $files = [];
        if ($event->order->isPaid()) {
            foreach ($resources as $resource) {
                $files[] = ['resource' => $resource, 'url' => $this->links->sign('classroom_download_file', ['id' => $resource->getId(), 'filename' => $resource->getFilename() ?? 'fichier'], 86400)];
            }
        }

        $event->setResponse(new Response($this->twig->render('@Classroom/client/thanks.html.twig', [
            'order' => $event->order,
            'resources' => $resources,
            'files' => $files,
        ])));
    }

    /** @return \Base\Classroom\Entity\Resource[] the files an order delivers */
    public static function resourcesOf(Order $order): array
    {
        $resources = [];
        foreach ($order->getItems() as $item) {
            $product = $item->getProduct();
            if ($product instanceof ResourceOffer && $product->getResource()) {
                $resources[$product->getResource()->getId()] = $product->getResource();
            }
        }

        return array_values($resources);
    }
}
