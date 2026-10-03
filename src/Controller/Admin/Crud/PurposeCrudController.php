<?php

namespace Base\Classroom\Controller\Admin\Crud;

use Base\Classroom\Entity\Purpose;
use Base\Controller\Backoffice\Crud\Thread\TaxonCrudController;
use Base\Field\IntegerField;
use Base\Admin\Config\Actions;
use Base\Classroom\Controller\Admin\OpenToAdminsTrait;

/** The purposes: omnibase's taxon screen, plus what a purpose adds. */
class PurposeCrudController extends TaxonCrudController
{
    use OpenToAdminsTrait;

    public function configureActions(Actions $actions): Actions
    {
        return $this->openToAdmins(parent::configureActions($actions));
    }

    public static function getEntityFqcn(): string
    {
        return Purpose::class;
    }

    public static function getPreferredIcon(): ?string
    {
        return 'fa-solid fa-thumbtack';
    }

    protected function taxonFields(string $pageName): iterable
    {
        yield IntegerField::new('position', '@classroom.admin.taxon.position')->setColumns(3);

    }
}
