<?php

namespace Base\Classroom\EventSubscriber;

use App\Entity\User;
use Base\Classroom\Entity\Entitlement;
use Base\Classroom\Entity\Product\ResourceOffer;
use Base\Classroom\Repository\EntitlementRepository;
use Base\Marketplace\Event\OrderPaidEvent;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Base\Marketplace\Entity\Order;
use Base\Marketplace\Service\QuickOrder;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * What a paid order delivers here: an Entitlement to each resource whose
 * offer it holds, for the buyer - and a mail with the link of the page where
 * the files are downloaded (omnibase/marketplace's quick order page, signed:
 * no account needed). A webhook and a redirect may both confirm the same
 * order: an entitlement that exists is left alone, and the mail goes once.
 */
final class OrderPaidSubscriber
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly EntitlementRepository $entitlements,
        private readonly ?LoggerInterface $logger = null,
        private readonly ?MailerInterface $mailer = null,
        private readonly ?QuickOrder $quickOrder = null,
        private readonly ?TranslatorInterface $translator = null,
        #[Autowire('%classroom.from_email%')] private readonly ?string $from = null,
    ) {
    }

    #[AsEventListener(event: OrderPaidEvent::class)]
    public function __invoke(OrderPaidEvent $event): void
    {
        $order = $event->order;
        $customer = $order->getCustomer();
        if (!$customer instanceof User) {
            return;
        }
        $written = false;
        foreach ($order->getItems() as $item) {
            $product = $item->getProduct();
            if (!$product instanceof ResourceOffer) {
                continue;
            }
            $resource = $product->getResource();
            if (!$resource) {
                $this->logger?->error('Resource offer {offer} has no resource: order {order} paid, nothing delivered.', ['offer' => (string) $product, 'order' => (string) $order->getReference()]);
                continue;
            }
            if ($this->entitlements->findOne($customer, $resource)) {
                continue;
            }
            $this->entityManager->persist(new Entitlement($customer, $resource, (string) $order->getReference()));
            $written = true;
        }
        if ($written) {
            $this->entityManager->flush();
            $this->mail($order, $customer);
        }
    }

    /** The files' page, mailed to the buyer: a failure here is logged, never a reason to undo the payment's confirmation. */
    private function mail(Order $order, User $customer): void
    {
        if (!$this->mailer || !$this->quickOrder || !$customer->getEmail()) {
            return;
        }

        try {
            $email = (new TemplatedEmail())
                ->to((string) $customer->getEmail())
                ->subject($this->translator?->trans('@classroom.shop.mail.subject', ['reference' => (string) $order->getReference()]) ?? 'Your files')
                ->htmlTemplate('@Classroom/email/files.html.twig')
                ->context(['order' => $order, 'resources' => QuickOrderFilesListener::resourcesOf($order), 'link' => $this->quickOrder->doneUrl($order)]);
            if ($this->from) {
                $email->from($this->from);
            }
            $this->mailer->send($email);
        } catch (\Throwable $e) {
            $this->logger?->error('Order {order} paid and delivered, but its mail could not be sent: {error}', ['order' => (string) $order->getReference(), 'error' => $e->getMessage()]);
        }
    }
}
