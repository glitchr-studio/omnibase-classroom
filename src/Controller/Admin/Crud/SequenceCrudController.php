<?php

namespace Base\Classroom\Controller\Admin\Crud;

use Base\Classroom\Controller\Admin\OpenToAdminsTrait;
use Base\Admin\Config\Actions;
use Base\Admin\Controller\AbstractCrudController;
use Base\Admin\Filter\Filters;
use Base\Classroom\Entity\Level;
use Base\Classroom\Entity\Purpose;
use Base\Classroom\Entity\Resource;
use Base\Classroom\Entity\Sequence;
use Base\Classroom\Entity\Subject;
use Base\Classroom\Enum\Period;
use Base\Field\BooleanField;
use Base\Field\DateTimeField;
use Base\Field\EditorField;
use Base\Field\IdField;
use Base\Field\ImageField;
use Base\Field\IntegerField;
use Base\Field\SelectField;
use Base\Field\SlugField;
use Base\Field\StateField;
use Base\Field\TextareaField;
use Base\Field\TextField;

/**
 * Writing a sequence: title, levels, subjects, purposes, period, duration,
 * objectives and competences (one per line), material, the presentation
 * (EditorJS, autosaved - and co-edited live when the relay runs), the
 * resources attached, the cover. Its sessions have their own screen.
 */
class SequenceCrudController extends AbstractCrudController
{
    use OpenToAdminsTrait;

    public static function getEntityFqcn(): string
    {
        return Sequence::class;
    }

    public static function getPreferredIcon(): ?string
    {
        return 'fa-solid fa-book-open';
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters->add('state')->add('periodValue')->add('featured');
    }

    public function configureActions(Actions $actions): Actions
    {
        return $this->openToAdmins(parent::configureActions($actions));
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();
        yield TextField::new('title', '@classroom.admin.sequence.title')->setColumns(8);
        yield StateField::new('state')->setColumns(4);
        yield SlugField::new('slug')->setColumns(4)->hideOnIndex();
        yield DateTimeField::new('publishedAt', '@classroom.admin.sequence.published_at')->setColumns(4)->hideOnIndex();
        yield BooleanField::new('featured', '@classroom.admin.sequence.featured')->setColumns(2);
        yield SelectField::new('taxa', '@classroom.admin.sequence.levels')->setClass(Level::class)->allowMultipleChoices()->setRequired(false)->setColumns(4);
        yield SelectField::new('taxa', '@classroom.admin.sequence.subjects')->setClass(Subject::class)->allowMultipleChoices()->setRequired(false)->setColumns(4);
        yield SelectField::new('taxa', '@classroom.admin.sequence.purposes')->setClass(Purpose::class)->allowMultipleChoices()->setRequired(false)->setColumns(4)->hideOnIndex();
        yield SelectField::new('periodValue', '@classroom.admin.sequence.period')->setChoices(array_combine(array_map(fn (Period $p) => 'P'.$p->number(), Period::cases()), array_map(fn (Period $p) => $p->value, Period::cases())))->setRequired(false)->setColumns(3);
        yield IntegerField::new('durationMinutes', '@classroom.admin.sequence.duration')->setColumns(3)->setRequired(false)->hideOnIndex();
        yield TextField::new('headline', '@classroom.admin.sequence.headline')->setColumns(12)->hideOnIndex();
        yield TextareaField::new('excerpt', '@classroom.admin.sequence.excerpt')->hideOnIndex();
        yield TextareaField::new('objectivesText', '@classroom.admin.sequence.objectives')->hideOnIndex()->setHelp('@classroom.admin.sequence.one_per_line');
        yield TextareaField::new('competencesText', '@classroom.admin.sequence.competences')->hideOnIndex()->setHelp('@classroom.admin.sequence.one_per_line');
        yield TextareaField::new('materials', '@classroom.admin.sequence.materials')->hideOnIndex();
        yield EditorField::new('content', '@classroom.admin.sequence.content')->hideOnIndex()->setFormTypeOption('collab_autosave', true)->setFormTypeOption('collab_live', true);
        yield TextareaField::new('embedsText', '@classroom.admin.embeds')->hideOnIndex()->setHelp('@classroom.admin.embeds_help');
        yield SelectField::new('resources', '@classroom.admin.sequence.resources')->setClass(Resource::class)->allowMultipleChoices()->setRequired(false)->setColumns(6)->hideOnIndex();
        yield ImageField::new('cover', '@classroom.admin.sequence.cover')->setColumns(6)->hideOnIndex();
    }

    public function createEntity(string $entityFqcn): object
    {
        $sequence = new Sequence();
        $user = $this->getUser();
        if ($user instanceof \Base\Entity\User) {
            $sequence->addOwner($user);
        }

        return $sequence;
    }
}
