# SEO growth plan

No search volume, difficulty, or ranking is invented here. Nothing in this plan sends email or creates a backlink. A page is added only when it answers a different question from the pages that already exist.

## Audiences

- Someone who wants a one-to-one video conversation with a stranger.
- Someone comparing that idea with older stranger-chat sites.
- Someone who wants to know what is recorded, how to leave, and how to report.
- Someone whose camera or microphone will not start.

## Query to page

Primary means the page's main intent. Secondary means the page can also answer it. "Existing ranking URL" stays blank until a Search Console export names one. Impressions and clicks stay blank until that same export is imported with `php artisan seo:queries`.

| Query | Intent | Target URL | Role | Existing ranking URL | Impressions | Clicks | Priority | Next action |
| --- | --- | --- | --- | --- | --- | --- | --- | --- |
| random video chat | Use a random video chat | `/random-video-chat` | Primary | Unknown | Unknown | Unknown | High | Import a query export before writing more pages. |
| Koko Meet | Find the product | `/` | Primary | Unknown | Unknown | Unknown | High | Keep the homepage specific to one-to-one video. |
| talk to strangers | Meet one stranger | `/talk-to-strangers` | Primary | Unknown | Unknown | Unknown | High | Link it from the homepage and the safety guide. |
| free video chat with strangers | Understand the cost of starting | `/random-video-chat` | Secondary | Unknown | Unknown | Unknown | Medium | Do not create a separate "free" page. Say what the product does today. |
| Omegle alternative | Compare with that kind of product | `/omegle-alternative` | Primary | Unknown | Unknown | Unknown | High | Keep the comparison limited to features this product has. |
| sites like Omegle | Same comparison | `/omegle-alternative` | Secondary | Unknown | Unknown | Unknown | Medium | Do not publish a second comparison. |
| random video chat with text | See that text exists in the call | `/features` | Primary | Unknown | Unknown | Unknown | Medium | Describe the in-call text chat only. |
| video chat privacy | Read the data practices | `/privacy` | Primary | Unknown | Unknown | Unknown | High | Keep the privacy page aligned with what is stored. |
| video chat safety | Leave, block, report | `/safety` | Primary | Unknown | Unknown | Unknown | High | Keep the safety page and the safety article distinct. |
| camera and microphone | Fix a failed call | `/help` | Primary | Unknown | Unknown | Unknown | Medium | Add a longer guide only after these pages are live. |

Unmatched queries from an export are marked "Do not create a page yet."

## Editorial list

Write these only as original pages, after a person checks them. Do not publish a batch of generated articles.

1. Camera and microphone troubleshooting, longer than `/help`, once the short page is not enough.
2. What a moderator can and cannot see. The product does not keep a recording.
3. Product changes that already shipped, dated from the real change.

The blog post on how a call works, the safety post, and the "what to expect" guide already exist. Update them instead of duplicating them.

## Outreach

Do not send these unless someone decides to contact a specific person.

Story angles: a one-to-one video chat that states what it does not record; a practical camera-permission guide; a comparison that refuses fake user counts.

Publisher categories: browser and WebRTC explainers, online-safety writers, startup communities that accept a straightforward product note.

Template:

> We built Koko Meet, a one-to-one random video chat. The attached page explains [one specific behavior]. There is no user-count claim. If it is useful to your readers, the page is [URL].

Track each attempt:

| Outlet | URL offered | Date | Response | Link earned |
| --- | --- | --- | --- | --- |
|  |  |  |  |  |

## Later languages

Add a language only when a full translation exists. Give it its own URL, a self-canonical, and a reciprocal hreflang pair with the English page. Do not generate country pages by swapping place names.
