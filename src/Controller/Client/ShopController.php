<?php

namespace Base\Classroom\Controller\Client;

use Base\Classroom\Entity\Product\ResourceOffer;
use Base\Classroom\Entity\Resource;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * The shop of a classroom: the files for sale, on one page. Each is bought
 * in one step with omnibase/marketplace's quick order - an e-mail address,
 * the payment, no account -, and the order's page lists the files
 * (EventSubscriber\QuickOrderFilesListener).
 */
class ShopController extends AbstractController
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    #[Route('/boutique', name: 'classroom_shop')]
    public function index(): Response
    {
        $offers = $this->entityManager->getRepository(ResourceOffer::class)->findBy([], ['id' => 'DESC']);

        return $this->render('@Classroom/client/shop.html.twig', [
            'offers' => array_values(array_filter($offers, static fn (ResourceOffer $offer) => self::sellable($offer))),
        ]);
    }

    /** For sale, its resource online with a file to hand over. */
    public static function sellable(ResourceOffer $offer): bool
    {
        $resource = $offer->getResource();

        return $offer->isForSell() && $resource instanceof Resource && $resource->isPublished() && $resource->hasFile();
    }
}
