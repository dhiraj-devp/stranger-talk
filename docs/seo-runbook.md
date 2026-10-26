# SEO runbook

## Check the public signals

```
php artisan seo:audit
```

Exit code 0 means the local checks found no errors. It does not mean Google indexed anything. Warnings are printed and do not fail the command.

The same findings appear on `/admin/seo` for an admin. That screen does not show rankings or traffic.

## Sitemap and robots

- `https://kokomeet.live/sitemap.xml` should list `/`, legal pages, `/blog`, `/guides`, and published indexable pages, posts, and guides.
- It should not list `/login`, `/video`, `/home`, `/admin`, drafts, or noindex content.
- `https://kokomeet.live/robots.txt` should allow `/`, disallow `/admin` and `/video`, and name the sitemap.
- A site-wide `Disallow: /` turns on only when an admin types `BLOCK SITE` in SEO settings. Do not enable that to "test" indexing.

## Redirects

- `/community-guidelines` is a 301 to `/guidelines`.
- `/guides/random-video-chat` is a 301 to `/guides/what-to-expect`.
- Adding a redirect whose source and target are the same is reported by `seo:audit`.

## Search Console, done by a person

1. Set the canonical base to `https://kokomeet.live`.
2. Add the site in Search Console and paste the HTML-tag token into the existing verification field.
3. Submit `https://kokomeet.live/sitemap.xml`.
4. Use URL Inspection on `/`, `/random-video-chat`, and `/privacy` after the sitemap is processed.
5. Export queries when data exists and run `php artisan seo:queries storage/app/seo/export.csv`.

The CSV stays out of git. The report is written to `storage/app/seo/query-report.md`.

## Tests

```
php artisan test --filter=SeoTest
```

Also run the video page test that renders the room, so the SEO pages do not get mistaken for a chat change:

```
php artisan test --filter=test_turn_secret_is_hidden_until_turn_is_configured
```

## Deploy

On the server, after the code is uploaded:

```
php artisan migrate --force
```

Do not run `php artisan config:cache` unless that is already your release step. Do not point this migrate at a database you have not backed up. The new migration adds pages with `firstOrCreate` and updates the homepage title only when it is still the previous stock sentence.
