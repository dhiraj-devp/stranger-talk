<?php

namespace Database\Seeders;

use App\Models\Content;
use App\Models\ContentCategory;
use App\Models\NavigationItem;
use App\Models\SiteSetting;
use App\Models\Tag;
use Illuminate\Database\Seeder;

class SeoSeeder extends Seeder
{
    public function run(): void
    {
        SiteSetting::query()->firstOrCreate(['site_name' => 'Koko Meet'], SiteSetting::defaults());

        $start = ContentCategory::query()->firstOrCreate(['slug' => 'getting-started'], [
            'name' => 'Getting started',
            'description' => 'How a Koko Meet call starts and what the controls do.',
        ]);
        $safety = ContentCategory::query()->firstOrCreate(['slug' => 'safety'], [
            'name' => 'Safety',
            'description' => 'Practical ways to keep a random video chat comfortable.',
        ]);
        $matching = Tag::query()->firstOrCreate(['slug' => 'matching'], ['name' => 'Matching']);
        $safetyTag = Tag::query()->firstOrCreate(['slug' => 'safety'], ['name' => 'Safety']);

        $about = $this->page('page', 'about', 'About Koko Meet', 'What Koko Meet is and what a call includes.', <<<'TXT'
Koko Meet is a random video chat for one-to-one conversations. You sign in, confirm a short profile, and get paired with one other person who is also looking.

There is no public room. Video and audio stay between the two browsers when a direct connection works. Koko Meet does not record the call.

## What you can do

You can mute yourself, turn the camera off, skip to someone else, or end the call. If a person should not be paired with you again, block them. If something breaks the community guidelines, report the call so a moderator can review the report.

Matching is random. Gender and country can be saved on your profile, but those preferences do not change who you are paired with.
TXT, $start->id, true);

        $this->page('page', 'safety', 'Staying safe on a random video chat', 'Practical safety steps for a one-to-one stranger video chat.', <<<'TXT'
A random video chat puts you on camera with someone you do not know. Treat the call as public in the moment, even though Koko Meet does not record it.

## Before you start

Use a current browser and allow the camera and microphone only if you want to be seen and heard. You can mute or turn the camera off during the call. Do not share your full name, address, school, workplace, or other accounts.

## During the call

Leave as soon as a conversation is uncomfortable. Next ends the current call and looks for someone else. Block stops that person from being paired with you again. Report sends the reason to moderators. They can read the report and account history. They cannot watch a recording, because none is kept.

## If you are under 18

Do not use Koko Meet for sexual conversation. Sexual content involving anyone under 18 is not allowed. Read the community guidelines before your first call.
TXT, $safety->id, true);

        $this->page('page', 'contact', 'Contact Koko Meet', 'How to reach the people who operate Koko Meet.', <<<'TXT'
Use the contact email published on this page when the site operator has added one. That address is for account, safety, and site questions.

For an urgent problem during a call, leave the call and use Report or Block. A contact email is not a live emergency service.

Safety questions are also covered in the safety guide and the community guidelines.
TXT, null, true);

        $how = $this->page('post', 'how-random-video-chat-works', 'How random video chat works', 'What happens from sign-in to a one-to-one video connection.', <<<'TXT'
Koko Meet matches two people who are both looking for a conversation. The pair is random. You are not placed into a group room.

## From sign-in to a call

You sign in with Google and, the first time, choose a gender and confirm a country. Those details are stored on the account. They are not used to filter matches.

When you start a search, the app looks for one other available person. If nobody is waiting, you stay in the queue. When someone is found, the browsers negotiate a direct video connection. If a relay server is configured and a direct connection cannot be made, media may pass through that relay for the length of the call. The site still does not store the video.

## Next and end

Next closes the current conversation and starts another search. End leaves the call without starting a new one. Closing the tab also leaves the call. The other person is told the conversation ended.

Koko Meet keeps a match record, such as when a call started or ended. It does not keep a transcript or a recording.
TXT, $start->id, true);

        $safePost = $this->page('post', 'how-to-stay-safe-talking-to-strangers', 'How to stay safe talking to strangers', 'A practical guide to ending, blocking, and reporting a video chat.', <<<'TXT'
Talking to a stranger on video is different from a text chat. You are visible, and the other person is too. Koko Meet does not record the call, but the other person can still see and hear whatever you share live.

## Keep identifying details off the call

Skip your full name, phone number, address, and the accounts you use elsewhere. A first name is already on the profile from Google. You do not need to add more.

## Use the controls you already have

Mute and camera-off are immediate. Next is how you leave one person and look for another. Block is how you avoid that person later. Report is how a moderator sees that a call broke the guidelines.

If someone asks you to move to another app, you can refuse and end the call. You do not owe a stranger a longer conversation.
TXT, $safety->id, true);

        $guide = $this->page('guide', 'random-video-chat', 'What to expect from a random video chat', 'The first minute of a Koko Meet call, the controls, and when to leave.', <<<'TXT'
This guide is for someone using Koko Meet for the first time. It explains the call itself, not how to rank in search or how to meet a specific person.

## The first minute

After you start a search, the status line tells you whether the app is still looking or connecting. A match is one other person. When the connection works, you see them and they see you, unless a camera is off.

Give the connection a moment. Calls fail when a browser blocks the camera, a network blocks direct video, or the other person leaves. Failure is normal. Start another search if you still want to talk.

## Decide quickly

You do not have to fill a silence. If the conversation is fine, talk. If it is not, use Next. Staying is optional.

Read the safety notes if you want the longer version of block, report, and what is not allowed.
TXT, $start->id, true);

        $how->tags()->sync([$matching->id]);
        $safePost->tags()->sync([$safetyTag->id]);
        $guide->tags()->sync([$matching->id]);
        $about->update(['related_ids' => [$how->id, $guide->id]]);
        $guide->update(['related_ids' => [$how->id], 'featured' => true]);
        Content::query()->where('slug', 'safety')->update(['related_ids' => [$safePost->id]]);

        $links = [
            ['header', 'How it works', '/#how', 1],
            ['header', 'Safety', '/#safety', 2],
            ['header', 'FAQ', '/#faq', 3],
            ['header', 'Blog', '/blog', 4],
            ['footer', 'About', '/about', 1],
            ['footer', 'Safety', '/safety', 2],
            ['footer', 'Blog', '/blog', 3],
            ['footer', 'Guides', '/guides', 4],
            ['footer', 'Privacy', '/privacy', 5],
            ['footer', 'Terms', '/terms', 6],
            ['footer', 'Community Guidelines', '/guidelines', 7],
            ['footer', 'Contact', '/contact', 8],
        ];
        foreach ($links as [$location, $label, $url, $sort]) {
            NavigationItem::query()->firstOrCreate(
                ['location' => $location, 'url' => $url],
                ['label' => $label, 'sort_order' => $sort]
            );
        }
    }

    private function page(string $type, string $slug, string $title, string $excerpt, string $body, ?int $categoryId, bool $index): Content
    {
        return Content::query()->firstOrCreate(['slug' => $slug], [
            'type' => $type,
            'title' => $title,
            'excerpt' => $excerpt,
            'body' => trim($body),
            'category_id' => $categoryId,
            'status' => 'published',
            'published_at' => now(),
            'seo_title' => $title,
            'seo_description' => $excerpt,
            'robots_index' => $index,
            'robots_follow' => true,
            'breadcrumb_title' => $title,
        ]);
    }
}
