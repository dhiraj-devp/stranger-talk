# SEO audit

This is a code audit of the Laravel 12 Blade site. It does not include Google Search Console, live rankings, or search volumes. No position is claimed.

## Already in place

| Finding | Severity | Where | Verification |
| --- | --- | --- | --- |
| Canonicals are built from `canonical_base` or `APP_URL`, not the request URL, so tracking parameters do not create a second canonical. | Info | `app/Models/SiteSetting.php` | View source on `/` and confirm `rel="canonical"` has no query string. |
| Production can 301 to the canonical host and HTTPS when `canonical_base` is set. Trailing slashes and uppercase paths 301. | Info | `app/Http/Middleware/SeoRedirects.php` | Request `/About` and a trailing slash. |
| Public pages are server-rendered. The video engine is not loaded on marketing pages. Login, video, account, and admin are `noindex`. | Info | `resources/views/layouts/marketing.blade.php`, `layouts/app.blade.php`, `video.blade.php` | View source. Confirm `webrtc.js` is absent on `/`. |
| Sitemap and robots exist. The site-wide block still requires the text `BLOCK SITE`. | Info | `SitemapController`, `RobotsController`, SEO settings | `php artisan seo:audit` |
| Titles, descriptions, Open Graph, Twitter cards, breadcrumbs, and Organization / WebSite / Article schema exist. Custom JSON-LD cannot save aggregate ratings. | Info | `app/Services/SeoBuilder.php`, `SeoController` | Homepage source contains `application/ld+json` that parses. |

## Defects and the fixes in this change

| Finding | Severity | Where | Fix | Verification |
| --- | --- | --- | --- | --- |
| Homepage title and description were the older stock strings, and a live row would not pick up a PHP-only default change. | Medium | `site_settings.default_title` | Defaults updated. A row changes only when both fields still equal the previous stock strings. | View source title on `/` after migrate. A customized title is left alone. |
| `/contact` was left out of the sitemap even though the page is public and indexable. | Medium | `SitemapController` | Published, indexable pages, posts, and guides are included. A canonical on another host is skipped. | `/sitemap.xml` contains `/contact` and not `/login`, `/video`, `/admin`, or `/home`. |
| Intent URLs for random video chat, talking to strangers, an Omegle alternative, features, and help did not exist. | High | Public routes | Those pages are published content. `/safety`, `/about`, and `/contact` were kept. | Each URL returns 200, one H1, and `index,follow`. |
| The guide already used the slug `random-video-chat`, so the new landing page could not share it. | Medium | `contents.slug` | The guide moved to `/guides/what-to-expect`. `/guides/random-video-chat` 301s there. | Request the old guide URL and confirm 301. |
| `/community-guidelines` was only a proposal. `/guidelines` is the real page. | Low | `routes/web.php` | 301 from `/community-guidelines` to `/guidelines`. No second guidelines page. | Request the alias and confirm 301. |
| Homepage and footer did not link the intent pages. | Medium | `landing.blade.php`, `public-footer.blade.php` | First screen explains who it is for and how to start. Footer links About, Contact, Privacy, Terms, Safety, Guidelines, and Help. | Open `/` and the footer. |
| There was no repeatable SEO check. | Medium | Artisan | `php artisan seo:audit` prints severity, URL, and a fix. It does not mean Google indexed the URL. | The command exits 0 when the checks pass. |

## Left unchanged on purpose

- Matchmaking, WebRTC, text chat, Google sign-in, and moderation.
- `BLOCK SITE` confirmation.
- FAQ schema on the homepage, because those questions are visible. New landing pages do not add FAQ schema.
- No hreflang pages. No country or city doorway pages.
- No revision-history table.
- Core Web Vitals were not measured in the field. Public HTML is server-rendered, which is the property this repo can verify.
