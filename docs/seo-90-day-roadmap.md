# 90-day roadmap

Targets are actions, not rank positions. Do not treat a finished week as proof of indexing.

## Weeks 1–2

- Deploy the landing pages, the guide redirect, and the sitemap change.
- Set `canonical_base` to `https://kokomeet.live` if it is empty.
- Run `php artisan seo:audit` on the server after migrate.
- In Search Console, add the property, paste the verification token into SEO settings, and submit `https://kokomeet.live/sitemap.xml`.

## Weeks 3–4

- Export queries and pages from Search Console.
- Run `php artisan seo:queries` on that file.
- Fill the blank impression and click cells in `docs/seo-growth-plan.md` from that export only.
- Leave unmatched queries without a new URL.

## Weeks 5–8

- Read the top queries that already have a target URL. Adjust the title or the first paragraph only when the export shows the query and the page text does not answer it.
- Publish one camera and microphone guide if `/help` is getting impressions and the short page is not enough. Review it before it is published.
- Check that `/login`, `/video`, and `/admin` are still absent from the sitemap.

## Weeks 9–12

- Record, from Search Console, indexed and excluded URL samples. Do not invent the counts.
- Note which landing page precedes a sign-in in the existing analytics, if the GA4 id is configured. Do not add a new tracker for this.
- Decide whether any outreach row in the growth plan is worth sending. Sending is a separate decision.
- Re-run `php artisan seo:audit` after content edits.
