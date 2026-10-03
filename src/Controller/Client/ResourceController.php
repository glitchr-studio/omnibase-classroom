<?php

namespace Base\Classroom\Controller\Client;

use Base\Classroom\Entity\Resource;
use Base\Classroom\Repository\EntitlementRepository;
use Base\Classroom\Repository\ResourceRepository;
use Base\Classroom\Security\ResourceVoter;
use Base\Service\DownloadLinks;
use Base\Classroom\Service\SpreadsheetPreview;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;

/**
 * A resource's page - its preview (a picture, or the first rows of a
 * spreadsheet), its levels and subjects, the sequences it belongs to, the
 * offer when it is for sale - and its download in two steps: the button
 * checks who you are (ResourceVoter) and redirects to a signed, short-lived
 * file URL, served by nginx (X-Accel-Redirect to classroom.accel_prefix) or,
 * without it, by PHP.
 */
class ResourceController extends AbstractController
{
    public function __construct(
        private readonly ResourceRepository $resources,
        private readonly EntitlementRepository $entitlements,
        private readonly EntityManagerInterface $entityManager,
        private readonly DownloadLinks $links,
        private readonly SpreadsheetPreview $preview,
        #[Autowire('%classroom.accel_prefix%')] private readonly string $accelPrefix = '',
        #[Autowire('%classroom.download_ttl%')] private readonly int $downloadTtl = 600,
    ) {
    }

    #[Route('/ressources/{slug}', name: 'classroom_resource', requirements: ['slug' => '[a-z0-9\-]+'])]
    public function show(string $slug): Response
    {
        $resource = $this->resources->findOnePublished($slug) ?? throw $this->createNotFoundException();
        $offer = null;
        if ($resource->isPaid() && class_exists(\Base\Classroom\Entity\Product\ResourceOffer::class) && class_exists(\Base\Marketplace\Entity\Product::class)) {
            $offer = $this->entityManager->getRepository(\Base\Classroom\Entity\Product\ResourceOffer::class)->findOneBy(['resource' => $resource]);
        }

        return $this->render('@Classroom/client/resource.html.twig', [
            'resource' => $resource,
            'sheets' => $this->preview->sheets($resource),
            'allowed' => $this->isGranted(ResourceVoter::DOWNLOAD, $resource),
            'offer' => $offer,
        ]);
    }

    #[Route('/ressources/{slug}/telecharger', name: 'classroom_download', requirements: ['slug' => '[a-z0-9\-]+'])]
    public function download(string $slug): Response
    {
        $resource = $this->resources->findOnePublished($slug) ?? throw $this->createNotFoundException();
        if (!$resource->hasFile()) {
            throw $this->createNotFoundException('No file on this resource.');
        }
        if (!$this->isGranted(ResourceVoter::DOWNLOAD, $resource)) {
            if (!$this->getUser()) {
                return $this->redirectToRoute('security_login', ['_target_path' => $this->generateUrl('classroom_download', ['slug' => $slug])]);
            }
            $this->addFlash('classroom', $resource->isPaid() ? 'download.paid_only' : 'download.members_only');

            return $this->redirectToRoute('classroom_resource', ['slug' => $slug]);
        }
        $entitlement = $this->entitlements->findOne($this->getUser(), $resource);

        return $this->redirect($this->links->sign('classroom_download_file', ['id' => $resource->getId(), 'filename' => $resource->getFilename() ?? 'fichier'] + array_filter(['entitlement' => $entitlement?->getId()]), $this->downloadTtl));
    }

    #[Route('/ressources/fichier/{id}/{filename}', name: 'classroom_download_file', requirements: ['id' => '\d+', 'filename' => '[^/]+'])]
    public function serve(Request $request, int $id): Response
    {
        // A plain 403: an expired or forged link is not solved by signing in.
        if (!$this->links->verify($request)) {
            throw new AccessDeniedHttpException('This download link is invalid or has expired.');
        }
        $resource = $this->resources->find($id) ?? throw $this->createNotFoundException();
        $file = $resource->getFile();
        if (!$file || !is_file($file->getPathname())) {
            throw $this->createNotFoundException('The file is missing from the storage.');
        }

        // One download, one count: not a HEAD, not a resumed download.
        if ('GET' === $request->getMethod() && !$request->headers->has('Range')) {
            $resource->countDownload();
            if ($request->query->getInt('entitlement')) {
                $this->entitlements->find($request->query->getInt('entitlement'))?->countDownload();
            }
            $this->entityManager->flush();
        }

        $filename = $resource->getFilename() ?? $file->getFilename();
        $disposition = HeaderUtils::makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $filename, preg_replace('/[^A-Za-z0-9._-]/', '_', $filename));

        if ('' !== $this->accelPrefix) {
            $response = new Response();
            $response->headers->set('X-Accel-Redirect', rtrim($this->accelPrefix, '/').'/'.ltrim(str_replace(\dirname($file->getPathname(), 3), '', $file->getPathname()), '/'));
            $response->headers->set('Content-Type', $resource->getMimeType() ?: 'application/octet-stream');
            $response->headers->set('Content-Disposition', $disposition);

            return $response;
        }

        $response = new BinaryFileResponse($file->getPathname());
        $response->headers->set('Content-Type', $resource->getMimeType() ?: 'application/octet-stream');
        $response->headers->set('Content-Disposition', $disposition);
        $response->setPrivate();

        return $response;
    }
}
