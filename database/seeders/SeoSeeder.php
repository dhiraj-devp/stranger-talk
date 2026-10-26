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
        $this->syncGrowth();

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
            ['footer', 'Features', '/features', 9],
            ['footer', 'Help', '/help', 10],
            ['footer', 'Random video chat', '/random-video-chat', 11],
            ['footer', 'Talk to strangers', '/talk-to-strangers', 12],
            ['footer', 'Omegle alternative', '/omegle-alternative', 13],
        ];
        foreach ($links as [$location, $label, $url, $sort]) {
            NavigationItem::query()->firstOrCreate(
                ['location' => $location, 'url' => $url],
                ['label' => $label, 'sort_order' => $sort]
            );
        }
    }

    public function syncGrowth(): void
    {
        $oldTitle = 'Koko Meet — random video chat with strangers';
        $oldDescription = 'Koko Meet pairs you with one person for a live video conversation. Sign in with Google, get matched at random, and talk one-to-one.';
        $defaults = SiteSetting::defaults();
        SiteSetting::query()
            ->where('default_title', $oldTitle)
            ->where('default_description', $oldDescription)
            ->update([
                'default_title' => $defaults['default_title'],
                'default_description' => $defaults['default_description'],
            ]);
        SiteSetting::forgetCache();

        $guide = Content::query()->where('type', 'guide')->where('slug', 'random-video-chat')->first();
        if ($guide) {
            $guide->update(['slug' => 'what-to-expect']);
            \App\Models\SeoRedirect::query()->firstOrCreate(
                ['from_path' => '/guides/random-video-chat'],
                ['to_path' => '/guides/what-to-expect', 'status_code' => 301, 'enabled' => true]
            );
            \App\Models\SeoRedirect::forgetCache();
        }
        $guideId = Content::query()->where('type', 'guide')->where('slug', 'what-to-expect')->value('id');
        $pages = [
            ['random-video-chat', 'Random video chat', 'How a one-to-one random video chat starts on Koko Meet, from Google sign-in to Next and End.', <<<'TXT'
A random video chat on Koko Meet is one live call with one other person. You are not put in a group room.

## How it starts

[Sign in](/login) with Google. The first time, confirm your gender and country. Then start a search. When someone else is also waiting, the two browsers connect. If nobody is waiting, you stay in the queue.

## During the call

You see two equal video panels. A text chat sits beside the video. Mute, Next, and End are on the call screen. Next asks you to confirm before it leaves the person and looks for someone else. End leaves without starting another search.

Koko Meet does not store the video or audio. Read [Safety](/safety) and [Privacy](/privacy) before you start. The guide [What to expect from a random video chat](/guides/what-to-expect) covers the first minute.
TXT],
            ['talk-to-strangers', 'Talk to strangers online', 'What the other person can see when you talk to a stranger, and how to leave, block, or report.', <<<'TXT'
Talking to a stranger here means one video conversation with one person you have not chosen.

## What the other person can see

They see and hear what you share live. A first name from your Google account can appear on the panel, with your country when it is set. Do not add a phone number, address, school, or another account.

## How to leave

Next ends this match and looks for another person. End stops without a new search. Block keeps that person from being paired with you again. Report sends the reason you write to a moderator. There is no recording for them to watch.

Start from [Sign in](/login). The longer notes are on [Safety](/safety) and [Privacy](/privacy).
TXT],
            ['omegle-alternative', 'An Omegle alternative for one-to-one chat', 'What Koko Meet includes if you want a one-to-one video chat, and what it does not copy from other sites.', <<<'TXT'
People looking for an Omegle alternative usually want a video conversation with a stranger. Koko Meet is a one-to-one video chat. It is not a copy of any other product, and it does not offer a group room or a text-only stranger chat.

## What a call includes

You [sign in](/login) with Google. You are paired with one other person who is also looking. The call has video, audio, and a text chat for that match. Mute, Next, and End are on the screen. Next asks for confirmation while a call is connected. Report and block are available if you want that person stopped.

## What it does not include

Koko Meet does not record the call. Moderators can read a report you submit. They cannot watch a recording. Gender and country can be saved on the account. Those preferences do not change who you meet until that option is turned on.

Read [Features](/features), [Safety](/safety), and [Privacy](/privacy) for the details that are actually supported.
TXT],
            ['features', 'Koko Meet features', 'The call controls Koko Meet actually has: video, text chat, mute, Next, End, report, and block.', <<<'TXT'
These are the controls on a Koko Meet call. Nothing else is implied.

## On the call

Two equal video panels show you and the other person. Your preview is a mirror. Their picture is not. Name and country appear on each panel when a match starts. Mute turns your microphone off. Text chat sends a short message during the call. Next, after a confirmation, leaves this person and looks for another. End leaves the call. Report and block are in the menu.

## Camera and microphone

You pick a camera and microphone from the menu. The camera stays on when you enter the room. There is no separate camera on/off button in the dock. The site does not ask for the camera on the public pages. The browser asks when you open the call.

## Preferences

You can save a gender and a country preference. Matching does not use them until that option is turned on. Blocks and bans still apply.

[Sign in](/login) to start. [Safety](/safety), [Privacy](/privacy), and [Help](/help) cover leaving a call and fixing a camera that will not start.
TXT],
            ['help', 'Help with camera and connection problems', 'What to try when the camera, microphone, or connection fails before or during a call.', <<<'TXT'
Most failed calls are a blocked camera, a blocked microphone, or a network that cannot connect the two browsers.

## Camera or microphone

Use a current browser. When the call page opens, allow the camera and microphone if you want to be seen and heard. If you blocked them earlier, change the permission in the browser's site settings and reload. You can choose a different camera or microphone from the menu on the call screen.

## Connection

The status line says whether the app is still looking or connecting. If the other person leaves, the call ends. If a direct connection cannot be made and a relay is configured, media may pass through that relay for the length of the call. Koko Meet still does not store the video.

## If the conversation is the problem

Leave. Use End, or Next if you want another person. Block and report are for someone who should not be paired with you again, or who broke the [guidelines](/guidelines).

[Safety](/safety) and [Privacy](/privacy) explain what is stored. [Sign in](/login) when you are ready to try again.
TXT],
        ];

        foreach ($pages as [$slug, $title, $excerpt, $body]) {
            $page = $this->page('page', $slug, $title, $excerpt, $body, null, true);
            if ($page->wasRecentlyCreated && $guideId) {
                $page->update(['related_ids' => [$guideId]]);
            }
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
