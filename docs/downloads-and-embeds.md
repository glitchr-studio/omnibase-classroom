# Downloads and embeds

## Signed downloads

A resource's file is downloaded through a signed link (glitchr/omnibase's
`Base\Service\DownloadLinks`): `ResourceController` signs
`classroom_download_file` for `classroom.download_ttl` seconds once
`ResourceVoter` let the visitor through, and checks the signature when the file
is asked for; after a quick order, `QuickOrderFilesListener` signs a link for a
day. The bundle no longer has a DownloadLinks of its own.

## What a sequence or a card frames

The "Intégrations" of a sequence or a card (one address per line, "Label |
https://…") are framed by glitchr/omnibase's Twig function `embed_url()`: Canva,
Padlet, YouTube, Vimeo, LearningApps, Genially and Google Slides are read from
the address; any other site is asked for its player (embed/embed, oEmbed) and
framed when it has one, a plain link otherwise. `classroom_embed()` and
`Base\Classroom\Service\Embeds` are gone; `@Classroom/client/_embeds.html.twig`
uses `embed_url()` (`embed.open.other` labels a provider not known by name).
