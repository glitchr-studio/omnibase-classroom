<?php

namespace Base\Classroom\Controller\Admin\Crud;

use Base\Classroom\Controller\Admin\OpenToAdminsTrait;
use Base\Admin\Attribute\AdminAction;
use Base\Admin\Config\Action;
use Base\Admin\Config\Actions;
use Base\Admin\Controller\AbstractCrudController;
use Base\Admin\Filter\Filters;
use Base\Classroom\Entity\Level;
use Base\Classroom\Entity\Resource;
use Base\Classroom\Entity\Subject;
use Base\Classroom\Enum\Visibility;
use Base\Field\BooleanField;
use Base\Field\FileField;
use Base\Field\IdField;
use Base\Field\ImageField;
use Base\Field\IntegerField;
use Base\Field\SelectField;
use Base\Field\SlugField;
use Base\Field\TextareaField;
use Base\Field\TextField;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * The resources: the file (on the private storage), its saved name, who it
 * is for, its preview picture, its levels and subjects. "Mettre en vente"
 * creates the shop's offer for a paid one in one click (omnibase/marketplace).
 */
class ResourceCrudController extends AbstractCrudController
{
    use OpenToAdminsTrait;

    public static function getEntityFqcn(): string
    {
        return Resource::class;
    }

    public static function getPreferredIcon(): ?string
    {
        return 'fa-solid fa-file-arrow-down';
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters->add('visibilityValue')->add('published');
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();
        yield TextField::new('title', '@classroom.admin.resource.title')->setColumns(6);
        yield SelectField::new('visibilityValue', '@classroom.admin.resource.visibility')->setChoices(array_combine(array_map(fn (Visibility $v) => '@classroom.resource.visibility.'.$v->value, Visibility::cases()), array_map(fn (Visibility $v) => $v->value, Visibility::cases())))->setColumns(3);
        yield BooleanField::new('published', '@classroom.admin.resource.published')->setColumns(3);
        yield SlugField::new('slug')->setColumns(4)->hideOnIndex();
        yield FileField::new('file', '@classroom.admin.resource.file')->setColumns(8)->hideOnIndex();
        yield TextField::new('filename', '@classroom.admin.resource.filename')->setColumns(4)->setRequired(false)->hideOnIndex()->setHelp('@classroom.admin.resource.filename_help');
        yield TextareaField::new('description', '@classroom.admin.resource.description')->hideOnIndex();
        yield SelectField::new('taxa', '@classroom.admin.sequence.levels')->setClass(Level::class)->allowMultipleChoices()->setRequired(false)->setColumns(6);
        yield SelectField::new('taxa', '@classroom.admin.sequence.subjects')->setClass(Subject::class)->allowMultipleChoices()->setRequired(false)->setColumns(6);
        yield ImageField::new('preview', '@classroom.admin.resource.preview')->setColumns(6)->hideOnIndex();
        yield IntegerField::new('downloads', '@classroom.admin.resource.downloads')->onlyOnIndex();
    }

    public function configureActions(Actions $actions): Actions
    {
        $actions = $this->openToAdmins(parent::configureActions($actions), 'sell');
        if (class_exists(\Base\Marketplace\Entity\Product::class)) {
            foreach ([Actions::PAGE_INDEX, Actions::PAGE_DETAIL] as $page) {
                $actions->add($page, Action::new('sell', '@classroom.admin.resource.action.sell', 'fa-solid fa-tag')->linkToCrudAction('sell'));
            }
        }

        return $actions;
    }

    /** Before the file lands: its name, type and size kept on the row. */
    public function persistEntity(EntityManagerInterface $entityManager, object $entity): void
    {
        $this->describeFile($entity);
        parent::persistEntity($entityManager, $entity);
    }

    public function updateEntity(EntityManagerInterface $entityManager, object $entity): void
    {
        $this->describeFile($entity);
        parent::updateEntity($entityManager, $entity);
    }

    private function describeFile(object $resource): void
    {
        if (!$resource instanceof Resource) {
            return;
        }
        $file = $resource->getFile();
        if ($file && is_file($file->getPathname())) {
            $resource->setSize($file->getSize() ?: null);
            $resource->setMimeType($file instanceof \Symfony\Component\HttpFoundation\File\UploadedFile ? $file->getClientMimeType() : ($file->getMimeType() ?: null));
            if (!$resource->getFilename()) {
                $resource->setFilename($file instanceof \Symfony\Component\HttpFoundation\File\UploadedFile ? $file->getClientOriginalName() : $file->getFilename());
            }
        }
    }

    /** The shop's offer for this resource, made (or found) and opened for its price. */
    #[AdminAction('/{entityId}/sell')]
    public function sell(string $entityId): Response
    {
        /** @var Resource $resource */
        $resource = $this->findEntity($entityId);
        $offer = $this->entityManager->getRepository(\Base\Classroom\Entity\Product\ResourceOffer::class)->findOneBy(['resource' => $resource]);
        if (!$offer) {
            $store = $this->entityManager->getRepository(\Base\Marketplace\Entity\Store::class)->findOneBy([], ['id' => 'ASC']);
            $offer = new \Base\Classroom\Entity\Product\ResourceOffer(null, $store);
            $offer->setTitle($resource->getTitle());
            $offer->setExcerpt($resource->getDescription());
            $offer->setResource($resource);
            $offer->setState(\Base\Enum\ThreadState::DRAFT);
            $resource->setVisibility(Visibility::PAID);
            $this->entityManager->persist($offer);
            $this->entityManager->flush();
            $this->addFlash('success', '@classroom.admin.resource.flash.offer_created');
        }

        return $this->redirect($this->generateUrl('admin_crud_resource_offers_edit', ['entityId' => $offer->getId()]));
    }
}
