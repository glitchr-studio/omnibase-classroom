<?php

namespace Base\Classroom\Controller\Client;

use Base\Attributes\Attribute\Sitemap;
use Base\Classroom\Entity\Level;
use Base\Classroom\Entity\Purpose;
use Base\Classroom\Entity\Subject;
use Base\Classroom\Enum\Period;
use Base\Classroom\Repository\CardRepository;
use Base\Classroom\Repository\ResourceRepository;
use Base\Classroom\Repository\SequenceRepository;
use Base\Classroom\Service\Taxa;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * The classroom read by level, by subject, by purpose - the way a colleague
 * looks for things: "CP › Allemand", "Multi-niveaux › Arts", "Pour la
 * classe › Rituels" - each page listing the sequences, the cards and the
 * resources that match, in the chronicle's order (newest first), by period
 * when asked. Then the sequences, the cards, the resources on their own,
 * and a search across all three.
 */
class ClassroomController extends AbstractController
{
    public function __construct(
        private readonly Taxa $taxa,
        private readonly SequenceRepository $sequences,
        private readonly CardRepository $cards,
        private readonly ResourceRepository $resources,
        #[Autowire('%classroom.per_page%')] private readonly int $perPage = 24,
    ) {
    }

    #[Sitemap(priority: 0.8, changefreq: 'weekly')]
    #[Route('/classe', name: 'classroom_index')]
    public function index(): Response
    {
        return $this->render('@Classroom/client/index.html.twig', [
            'cycles' => $this->taxa->cycles(),
            'subjects' => $this->taxa->subjects(),
            'purposes' => $this->taxa->purposes(),
            'sequences' => $this->sequences->findLatest(6),
            'cards' => $this->cards->findFeatured(6),
        ]);
    }

    #[Route('/classe/{level}/{subject}', name: 'classroom_level', requirements: ['level' => '[a-z0-9\-]+', 'subject' => '[a-z0-9\-]+'], defaults: ['subject' => null])]
    public function level(Request $request, string $level, ?string $subject): Response
    {
        $levelTaxon = $this->taxa->level($level) ?? throw $this->createNotFoundException();
        $subjectTaxon = $subject ? ($this->taxa->subject($subject) ?? throw $this->createNotFoundException()) : null;

        return $this->listing($request, [$levelTaxon, $subjectTaxon], 'classroom_level', ['level' => $level, 'subject' => $subject]);
    }

    #[Route('/matiere/{subject}', name: 'classroom_subject', requirements: ['subject' => '[a-z0-9\-]+'])]
    public function subject(Request $request, string $subject): Response
    {
        $subjectTaxon = $this->taxa->subject($subject) ?? throw $this->createNotFoundException();

        return $this->listing($request, [$subjectTaxon], 'classroom_subject', ['subject' => $subject]);
    }

    #[Route('/pour-la-classe/{purpose}', name: 'classroom_purpose', requirements: ['purpose' => '[a-z0-9\-]+'])]
    public function purpose(Request $request, string $purpose): Response
    {
        $purposeTaxon = $this->taxa->purpose($purpose) ?? throw $this->createNotFoundException();

        return $this->listing($request, [$purposeTaxon], 'classroom_purpose', ['purpose' => $purpose]);
    }

    #[Sitemap(priority: 0.7, changefreq: 'weekly')]
    #[Route('/sequences', name: 'classroom_sequences')]
    public function sequences(Request $request): Response
    {
        return $this->listing($request, [], 'classroom_sequences', [], 'sequences');
    }

    #[Route('/sequences/{slug}', name: 'classroom_sequence', requirements: ['slug' => '[a-z0-9\-]+'])]
    public function sequence(string $slug): Response
    {
        $sequence = $this->sequences->findOnePublished($slug) ?? throw $this->createNotFoundException();

        return $this->render('@Classroom/client/sequence.html.twig', [
            'sequence' => $sequence,
            'sessions' => $sequence->getSessions(),
            'related' => array_values(array_filter($this->sequences->findPublishedPage(1, 4, array_map(fn ($t) => $t->getSlug(), $sequence->getSubjects())), fn ($s) => $s !== $sequence)),
        ]);
    }

    #[Sitemap(priority: 0.7, changefreq: 'weekly')]
    #[Route('/cartes', name: 'classroom_cards')]
    public function cards(Request $request): Response
    {
        return $this->listing($request, [], 'classroom_cards', [], 'cards');
    }

    #[Route('/cartes/{slug}', name: 'classroom_card', requirements: ['slug' => '[a-z0-9\-]+'])]
    public function card(string $slug): Response
    {
        $card = $this->cards->findOnePublished($slug) ?? throw $this->createNotFoundException();
        $all = $this->cards->findFeatured(12);
        $index = array_search($card, $all, true);

        return $this->render('@Classroom/client/card.html.twig', [
            'card' => $card,
            'previous' => false !== $index ? ($all[$index - 1] ?? null) : null,
            'next' => false !== $index ? ($all[$index + 1] ?? null) : null,
        ]);
    }

    #[Sitemap(priority: 0.7, changefreq: 'weekly')]
    #[Route('/ressources', name: 'classroom_resources')]
    public function resources(Request $request): Response
    {
        return $this->listing($request, [], 'classroom_resources', [], 'resources');
    }

    #[Route('/rechercher', name: 'classroom_search')]
    public function search(Request $request): Response
    {
        $term = trim((string) $request->query->get('q', ''));
        $long = mb_strlen($term) >= 3;

        return $this->render('@Classroom/client/search.html.twig', [
            'term' => $term,
            'sequences' => $long ? $this->sequences->search($term) : [],
            'cards' => $long ? $this->cards->search($term) : [],
            'resources' => $long ? $this->resources->search($term) : [],
        ]);
    }

    /**
     * One page for every way in: the taxa chosen (a level, a subject, a
     * purpose - the pills), the period asked (?periode=p1), the kind shown
     * (all, sequences, cards, resources), newest first.
     *
     * @param list<Level|Subject|Purpose|null> $taxa
     */
    private function listing(Request $request, array $taxa, string $route, array $params, string $kind = 'all'): Response
    {
        $taxa = array_values(array_filter($taxa));
        $slugs = array_map(fn ($t) => $t->getSlug(), $taxa);
        $period = Period::tryFrom((string) $request->query->get('periode', ''));
        $page = max(1, $request->query->getInt('page', 1));
        $kind = $request->query->get('voir', $kind);
        $kind = \in_array($kind, ['all', 'sequences', 'cards', 'resources'], true) ? $kind : 'all';

        $level = current(array_filter($taxa, fn ($t) => $t instanceof Level)) ?: null;
        $subject = current(array_filter($taxa, fn ($t) => $t instanceof Subject)) ?: null;
        $purpose = current(array_filter($taxa, fn ($t) => $t instanceof Purpose)) ?: null;

        return $this->render('@Classroom/client/listing.html.twig', [
            'level' => $level,
            'subject' => $subject,
            'purpose' => $purpose,
            'levels' => $this->taxa->levels(),
            'subjects' => $this->taxa->subjects(),
            'purposes' => $this->taxa->purposes(),
            'period' => $period,
            'periods' => Period::cases(),
            'kind' => $kind,
            'route' => $route,
            'params' => $params,
            'sequences' => 'cards' === $kind || 'resources' === $kind ? [] : $this->sequences->findPublishedPage($page, $this->perPage, $slugs, $period?->value),
            'sequences_pages' => 'sequences' === $kind ? max(1, (int) ceil($this->sequences->countPublished($slugs, $period?->value) / $this->perPage)) : 1,
            'cards' => 'sequences' === $kind || 'resources' === $kind ? [] : $this->cards->findPublished($slugs, 'cards' === $kind ? 200 : 8),
            'resources' => 'sequences' === $kind || 'cards' === $kind ? [] : $this->resources->findPublished($slugs, 'resources' === $kind ? 200 : 8),
            'page' => $page,
        ]);
    }
}
