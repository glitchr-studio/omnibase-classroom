<?php

namespace Base\Classroom\Controller\Admin\Crud;

use Base\Classroom\Entity\Product\ResourceOffer;
use Base\Classroom\Entity\Resource;
use Base\Field\SelectField;
use Base\Marketplace\Controller\Admin\Crud\ProductCrudController;

/** The resources for sale: the shop's product screen, plus the resource delivered. */
class ResourceOfferCrudController extends ProductCrudController
{
    public static function getEntityFqcn(): string
    {
        return ResourceOffer::class;
    }

    public static function getPreferredIcon(): ?string
    {
        return 'fa-solid fa-file-invoice-dollar';
    }

    public function configureFields(string $pageName): iterable
    {
        yield from parent::configureFields($pageName);
        yield SelectField::new('resource', '@classroom.admin.offer.resource')->setClass(Resource::class)->setColumns(6);
    }
}
