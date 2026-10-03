<?php

namespace Base\Classroom\Controller\Admin\Crud;

use Base\Classroom\Controller\Admin\OpenToAdminsTrait;
use Base\Admin\Config\Actions;
use Base\Admin\Controller\AbstractCrudController;
use Base\Admin\Filter\Filters;
use Base\Classroom\Entity\Sequence;
use Base\Classroom\Entity\Session;
use Base\Field\EditorField;
use Base\Field\IdField;
use Base\Field\IntegerField;
use Base\Field\SelectField;
use Base\Field\SlugField;
use Base\Field\StateField;
use Base\Field\TextareaField;
use Base\Field\TextField;

/** The sessions: which sequence, which place, how long, the aim, the course. */
class SessionCrudController extends AbstractCrudController
{
    use OpenToAdminsTrait;

    public static function getEntityFqcn(): string
    {
        return Session::class;
    }

    public static function getPreferredIcon(): ?string
    {
        return 'fa-solid fa-list-ol';
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters->add('parent');
    }

    public function configureActions(Actions $actions): Actions
    {
        return $this->openToAdmins(parent::configureActions($actions));
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();
        yield SelectField::new('parent', '@classroom.admin.session.sequence')->setClass(Sequence::class)->setColumns(6);
        yield IntegerField::new('position', '@classroom.admin.session.position')->setColumns(2);
        yield IntegerField::new('durationMinutes', '@classroom.admin.session.duration')->setColumns(2)->setRequired(false);
        yield StateField::new('state')->setColumns(2);
        yield TextField::new('title', '@classroom.admin.session.title')->setColumns(8);
        yield SlugField::new('slug')->setColumns(4)->hideOnIndex();
        yield TextField::new('headline', '@classroom.admin.session.aim')->setColumns(12)->hideOnIndex();
        yield TextareaField::new('materialsText', '@classroom.admin.session.materials')->hideOnIndex()->setHelp('@classroom.admin.sequence.one_per_line');
        yield EditorField::new('content', '@classroom.admin.session.content')->hideOnIndex()->setFormTypeOption('collab_autosave', true)->setFormTypeOption('collab_live', true);
    }

    public function createEntity(string $entityFqcn): object
    {
        $session = new Session();
        $session->setState(\Base\Enum\ThreadState::PUBLISH);
        $user = $this->getUser();
        if ($user instanceof \Base\Entity\User) {
            $session->addOwner($user);
        }

        return $session;
    }
}
