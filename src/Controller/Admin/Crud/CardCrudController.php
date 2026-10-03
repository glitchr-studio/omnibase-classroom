<?php

namespace Base\Classroom\Controller\Admin\Crud;

use Base\Classroom\Controller\Admin\OpenToAdminsTrait;
use Base\Admin\Config\Actions;
use Base\Admin\Controller\AbstractCrudController;
use Base\Admin\Filter\Filters;
use Base\Classroom\Entity\Card;
use Base\Classroom\Entity\Level;
use Base\Classroom\Entity\Subject;
use Base\Field\BooleanField;
use Base\Field\EditorField;
use Base\Field\IconField;
use Base\Field\IdField;
use Base\Field\ImageField;
use Base\Field\IntegerField;
use Base\Field\SelectField;
use Base\Field\SlugField;
use Base\Field\StateField;
use Base\Field\TextareaField;
use Base\Field\TextField;

/** The idea cards: a picture, a title, a line, a few paragraphs, up to three links, levels and subjects. */
class CardCrudController extends AbstractCrudController
{
    use OpenToAdminsTrait;

    public static function getEntityFqcn(): string
    {
        return Card::class;
    }

    public static function getPreferredIcon(): ?string
    {
        return 'fa-solid fa-clone';
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters->add('state')->add('featured');
    }

    public function configureActions(Actions $actions): Actions
    {
        return $this->openToAdmins(parent::configureActions($actions));
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();
        yield TextField::new('title', '@classroom.admin.card.title')->setColumns(6);
        yield StateField::new('state')->setColumns(3);
        yield BooleanField::new('featured', '@classroom.admin.card.featured')->setColumns(2);
        yield IntegerField::new('position', '@classroom.admin.card.position')->setColumns(1);
        yield SlugField::new('slug')->setColumns(4)->hideOnIndex();
        yield TextField::new('headline', '@classroom.admin.card.headline')->setColumns(8)->hideOnIndex();
        yield SelectField::new('taxa', '@classroom.admin.sequence.levels')->setClass(Level::class)->allowMultipleChoices()->setRequired(false)->setColumns(4);
        yield SelectField::new('taxa', '@classroom.admin.sequence.subjects')->setClass(Subject::class)->allowMultipleChoices()->setRequired(false)->setColumns(4);
        yield IconField::new('icon', '@classroom.admin.card.icon')->setColumns(4)->setRequired(false);
        yield TextField::new('color', '@classroom.admin.card.color')->setColumns(4)->setRequired(false)->hideOnIndex()->setHelp('@classroom.admin.card.color_help');
        yield EditorField::new('content', '@classroom.admin.card.content')->hideOnIndex()->setFormTypeOption('collab_autosave', true)->setFormTypeOption('collab_live', true);
        yield TextareaField::new('linksText', '@classroom.admin.card.links')->hideOnIndex()->setHelp('@classroom.admin.card.links_help');
        yield TextareaField::new('embedsText', '@classroom.admin.embeds')->hideOnIndex()->setHelp('@classroom.admin.embeds_help');
        yield ImageField::new('image', '@classroom.admin.card.image')->setColumns(6)->hideOnIndex();
    }

    public function createEntity(string $entityFqcn): object
    {
        $card = new Card();
        $user = $this->getUser();
        if ($user instanceof \Base\Entity\User) {
            $card->addOwner($user);
        }

        return $card;
    }
}
