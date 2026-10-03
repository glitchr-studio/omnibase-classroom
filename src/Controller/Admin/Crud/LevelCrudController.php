<?php

namespace Base\Classroom\Controller\Admin\Crud;

use Base\Classroom\Entity\Level;
use Base\Controller\Backoffice\Crud\Thread\TaxonCrudController;
use Base\Field\IntegerField;
use Base\Admin\Config\Actions;
use Base\Classroom\Controller\Admin\OpenToAdminsTrait;
use Base\Field\ColorPickerField;
use Base\Field\TextField;

/** The levels: omnibase's taxon screen, plus what a level adds. */
class LevelCrudController extends TaxonCrudController
{
    use OpenToAdminsTrait;

    public function configureActions(Actions $actions): Actions
    {
        return $this->openToAdmins(parent::configureActions($actions));
    }

    public static function getEntityFqcn(): string
    {
        return Level::class;
    }

    public static function getPreferredIcon(): ?string
    {
        return 'fa-solid fa-stairs';
    }

    protected function taxonFields(string $pageName): iterable
    {
        yield IntegerField::new('position', '@classroom.admin.taxon.position')->setColumns(3);
        yield TextField::new('short', '@classroom.admin.level.short')->setColumns(3)->setRequired(false);
        yield ColorPickerField::new('color', '@classroom.admin.taxon.color')->setColumns(3)->setRequired(false)->hideOnIndex();
    }
}
