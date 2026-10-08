# Classroom

A teacher's open classroom on [omnibase](https://github.com/glitchr-studio/omnibase):
what a colleague passing by may pick up - the way the French teachers' sites
(Lutin Bazar, Charivari, Orphée) organise things, built on omnibase's own
Thread and Taxon instead of a blog engine.

| Entity | Is a | What |
|---|---|---|
| `Level` | `Taxon` | a class level, in a tree of cycles: Cycle 2 › CP, CE1, CE2 |
| `Subject` | `Taxon` | a discipline, with its sub-domains: Français › Lecture, Grammaire… |
| `Purpose` | `Taxon` | "pour la classe": rituals, displays, autonomy, games, organisation |
| `Sequence` | `Thread` | a learning sequence: its objectives, competences, period (P1…P5), material, cover, and its `Session`s |
| `Session` | `Thread` (child of a Sequence) | one session: its duration and its course (EditorJS) |
| `Card` | `Thread` | an idea card: a picture, a few lines, one to three links - the fan of the home page |
| `Resource` | — | a file to download: PDF, document, spreadsheet, archive; free, for members, or paid |
| `Product\ResourceOffer` | marketplace `Product` | a paid resource for sale; paying it writes an `Entitlement` |

Levels, subjects and purposes are the `taxa` of the threads, so a sequence
is "CP and CE1 · Allemand" (multi-level: several levels on one thread) and
every list filters by them. The text of a sequence, a session or a card is
EditorJS, autosaved; with the yjs relay configured (`COLLAB_RELAY_WS_URL`) it
is co-edited live.

Resources live outside `public/` (the flysystem storage `local.classroom`,
which the host defines) and are served through signed, short-lived links -
by nginx (`accel_prefix`, X-Accel-Redirect) or by PHP. A spreadsheet (xlsx,
xls, csv) is previewed as a table (`preview_rows` rows) through PhpSpreadsheet;
an image through omnibase's thumbnails.

## Install

```bash
composer require omnibase/classroom:dev-main
```

```php
// config/bundles.php
Base\Classroom\ClassroomBundle::class => ['all' => true],
```

```yaml
# config/routes.yaml
classroom_controller:
    resource: "@ClassroomBundle/src/Controller/Client"
    type: attribute
    prefix: /

# config/packages/flysystem.yaml
flysystem:
    storages:
        local.classroom:
            local:
                directory: '%kernel.project_dir%/var/storage/classroom'

# config/packages/classroom.yaml (every key optional)
classroom:
    accel_prefix: ''        # nginx internal location, empty: PHP streams the file
    download_ttl: 600       # seconds a signed link lives
    preview_rows: 30        # rows of a spreadsheet shown in the preview
    members_role: ROLE_USER # who the MEMBERS resources are for
    per_page: 24
```

Then migrations and `assets:install` (`public/css/classroom.css`).

## Routes

`/classe/{level}`, `/classe/{level}/{subject}`, `/matiere/{subject}`,
`/pour-la-classe/{purpose}`, `/sequences`, `/sequences/{slug}`, `/cartes`,
`/cartes/{slug}`, `/ressources`, `/ressources/{slug}`,
`/ressources/{slug}/telecharger`, `/rechercher?q=`. Twig:
`classroom_levels()`, `classroom_subjects()`, `classroom_purposes()`,
`classroom_cards(limit)`, `classroom_sequences(limit)`.

The back office gets a CRUD for each entity and the dashboard widget
`classroom_overview` (downloads of the month, drafts, resources).

## The shop

`/boutique` (`classroom_shop`) lists the resources for sale, one card each,
and sells them with omnibase/marketplace's **quick order**: an e-mail
address, the payment, no account and no basket. The order's page - a signed
link, shown after the payment and mailed to the buyer
(`classroom.from_email` sets the sender) - lists the files, each behind a
signed download link. A signed-in member buys in their own name and finds
the file in their account as before.

## License

MIT since 2026-10-09; earlier versions remain published under LGPL-3.0-or-later.
