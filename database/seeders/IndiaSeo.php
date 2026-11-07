<?php

namespace Database\Seeders;

use App\Models\Content;
use App\Models\ContentCategory;
use App\Models\SiteSetting;

class IndiaSeo
{
    public function sync(): void
    {
        $defaults = SiteSetting::defaults();
        SiteSetting::query()->whereIn('default_title', [
            'Koko Meet — random video chat with strangers',
            'Koko Meet — Random Video Chat with Strangers',
        ])->update(['default_title' => $defaults['default_title']]);
        SiteSetting::query()->whereIn('default_description', [
            'Koko Meet pairs you with one person for a live video conversation. Sign in with Google, get matched at random, and talk one-to-one.',
            'Koko Meet is a one-to-one random video chat with text in the call. Read the privacy and safety notes, then sign in to start.',
        ])->update(['default_description' => $defaults['default_description']]);
        SiteSetting::query()->where('organization_description', 'Koko Meet is a random video chat for one-to-one conversations.')
            ->update(['organization_description' => $defaults['organization_description']]);
        SiteSetting::forgetCache();

        foreach ($this->replacements() as $row) {
            $page = Content::query()->where('slug', $row['slug'])->first();
            if (! $page || ! $this->same($page->body, $row['old'])) {
                continue;
            }
            $this->save($page, $row, true);
        }

        foreach ($this->pages() as $row) {
            $page = Content::query()->firstOrNew(['slug' => $row['slug']]);
            if ($page->exists && trim((string) $page->body) !== '') {
                continue;
            }
            $this->save($page, $row, true);
        }

        foreach ((new IndiaDrafts)->articles() as $row) {
            $page = Content::query()->firstOrNew(['slug' => $row['slug']]);
            if ($page->exists) {
                continue;
            }
            $this->save($page, $row, false);
        }
    }

    private function save(Content $page, array $row, bool $publish): void
    {
        $category = isset($row['category'])
            ? ContentCategory::query()->where('slug', $row['category'])->value('id')
            : null;
        $page->fill([
            'type' => $row['type'],
            'title' => $row['title'],
            'excerpt' => mb_substr($row['description'], 0, 240),
            'body' => trim($row['body']),
            'status' => $publish ? 'published' : 'draft',
            'seo_title' => $row['seo_title'],
            'seo_description' => $row['description'],
            'robots_index' => $publish,
            'robots_follow' => true,
            'breadcrumb_title' => $row['crumb'],
            'category_id' => $page->category_id ?: $category,
        ]);
        if ($publish && ! $page->published_at) {
            $page->published_at = now();
        }
        if (! $publish) {
            $page->published_at = null;
        }
        $page->save();
    }

    private function same(string $stored, string $stock): bool
    {
        $norm = fn (string $value): string => trim(str_replace("\r\n", "\n", $value));

        return $norm($stored) === $norm($stock);
    }

    private function replacements(): array
    {
        return [
            $this->row('page', 'about', 'About Koko Meet — One-to-One Random Video Chat in India', 'About Koko Meet — Random Video Chat in India | Koko Meet', 'What Koko Meet is, how sign-in and random matching work, and what a call does not include.', 'About', 'getting-started', $this->about(), <<<'TXT'
Koko Meet is a random video chat for one-to-one conversations. You sign in, confirm a short profile, and get paired with one other person who is also looking.

There is no public room. Video and audio stay between the two browsers when a direct connection works. Koko Meet does not record the call.

## What you can do

You can mute yourself, turn the camera off, skip to someone else, or end the call. If a person should not be paired with you again, block them. If something breaks the community guidelines, report the call so a moderator can review the report.

Matching is random. Gender and country can be saved on your profile, but those preferences do not change who you are paired with.
TXT),
            $this->row('page', 'safety', 'Stay Safe While Using Random Video Chat', 'Stay Safe on Random Video Chat | Koko Meet', 'How to leave, block, and report on Koko Meet, and what not to share with a stranger.', 'Safety', 'safety', $this->safety(), <<<'TXT'
A random video chat puts you on camera with someone you do not know. Treat the call as public in the moment, even though Koko Meet does not record it.

## Before you start

Use a current browser and allow the camera and microphone only if you want to be seen and heard. You can mute or turn the camera off during the call. Do not share your full name, address, school, workplace, or other accounts.

## During the call

Leave as soon as a conversation is uncomfortable. Next ends the current call and looks for someone else. Block stops that person from being paired with you again. Report sends the reason to moderators. They can read the report and account history. They cannot watch a recording, because none is kept.

## If you are under 18

Do not use Koko Meet for sexual conversation. Sexual content involving anyone under 18 is not allowed. Read the community guidelines before your first call.
TXT),
            $this->row('page', 'contact', 'Contact Koko Meet', 'Contact Koko Meet | Koko Meet', 'How to reach Koko Meet for account and safety questions. This email is not an emergency service.', 'Contact', null, $this->contact(), <<<'TXT'
Use the contact email published on this page when the site operator has added one. That address is for account, safety, and site questions.

For an urgent problem during a call, leave the call and use Report or Block. A contact email is not a live emergency service.

Safety questions are also covered in the safety guide and the community guidelines.
TXT),
            $this->row('page', 'features', 'Koko Meet Features — Random Video and Text Chat', 'Koko Meet Features — Random Video and Text Chat | Koko Meet', 'The call controls Koko Meet actually has: random matching, live video, text chat, mute, Next, End, block, and report.', 'Features', null, $this->features(), <<<'TXT'
These are the controls on a Koko Meet call. Nothing else is implied.

## On the call

Two equal video panels show you and the other person. Your preview is a mirror. Their picture is not. Name and country appear on each panel when a match starts. Mute turns your microphone off. Text chat sends a short message during the call. Next, after a confirmation, leaves this person and looks for another. End leaves the call. Report and block are in the menu.

## Camera and microphone

You pick a camera and microphone from the menu. The camera stays on when you enter the room. There is no separate camera on/off button in the dock. The site does not ask for the camera on the public pages. The browser asks when you open the call.

## Preferences

You can save a gender and a country preference. Matching does not use them until that option is turned on. Blocks and bans still apply.

[Sign in](/login) to start. [Safety](/safety), [Privacy](/privacy), and [Help](/help) cover leaving a call and fixing a camera that will not start.
TXT),
            $this->row('page', 'help', 'Help with Camera and Microphone Problems', 'Camera and Microphone Help for Video Chat | Koko Meet', 'What to try when the camera, microphone, queue, or connection fails on Koko Meet.', 'Help', null, $this->help(), <<<'TXT'
Most failed calls are a blocked camera, a blocked microphone, or a network that cannot connect the two browsers.

## Camera or microphone

Use a current browser. When the call page opens, allow the camera and microphone if you want to be seen and heard. If you blocked them earlier, change the permission in the browser's site settings and reload. You can choose a different camera or microphone from the menu on the call screen.

## Connection

The status line says whether the app is still looking or connecting. If the other person leaves, the call ends. If a direct connection cannot be made and a relay is configured, media may pass through that relay for the length of the call. Koko Meet still does not store the video.

## If the conversation is the problem

Leave. Use End, or Next if you want another person. Block and report are for someone who should not be paired with you again, or who broke the [guidelines](/guidelines).

[Safety](/safety) and [Privacy](/privacy) explain what is stored. [Sign in](/login) when you are ready to try again.
TXT),
            $this->row('page', 'random-video-chat', 'Random Video Chat Online in India', 'Random Video Chat Online in India | Koko Meet', 'Start a random video chat in India with Koko Meet. Connect one-to-one, talk face-to-face and move on whenever you want.', 'Random video chat', null, $this->randomVideo(), <<<'TXT'
A random video chat on Koko Meet is one live call with one other person. You are not put in a group room.

## How it starts

[Sign in](/login) with Google. The first time, confirm your gender and country. Then start a search. When someone else is also waiting, the two browsers connect. If nobody is waiting, you stay in the queue.

## During the call

You see two equal video panels. A text chat sits beside the video. Mute, Next, and End are on the call screen. Next asks you to confirm before it leaves the person and looks for someone else. End leaves without starting another search.

Koko Meet does not store the video or audio. Read [Safety](/safety) and [Privacy](/privacy) before you start. The guide [What to expect from a random video chat](/guides/what-to-expect) covers the first minute.
TXT),
            $this->row('page', 'talk-to-strangers', 'Talk to Strangers Online Through Video Chat', 'Talk to Strangers Online in India | Koko Meet', 'Talk to new people through random one-to-one video chat. Start a live conversation on Koko Meet.', 'Talk to strangers', null, $this->strangers(), <<<'TXT'
Talking to a stranger here means one video conversation with one person you have not chosen.

## What the other person can see

They see and hear what you share live. A first name from your Google account can appear on the panel, with your country when it is set. Do not add a phone number, address, school, or another account.

## How to leave

Next ends this match and looks for another person. End stops without a new search. Block keeps that person from being paired with you again. Report sends the reason you write to a moderator. There is no recording for them to watch.

Start from [Sign in](/login). The longer notes are on [Safety](/safety) and [Privacy](/privacy).
TXT),
            $this->row('page', 'omegle-alternative', 'Looking for an Omegle Alternative in India?', 'Omegle Alternative in India – Koko Meet', 'Looking for an Omegle alternative in India? Try Koko Meet for random one-to-one video conversations and text chat.', 'Omegle alternative', null, $this->omegle(), <<<'TXT'
People looking for an Omegle alternative usually want a video conversation with a stranger. Koko Meet is a one-to-one video chat. It is not a copy of any other product, and it does not offer a group room or a text-only stranger chat.

## What a call includes

You [sign in](/login) with Google. You are paired with one other person who is also looking. The call has video, audio, and a text chat for that match. Mute, Next, and End are on the screen. Next asks for confirmation while a call is connected. Report and block are available if you want that person stopped.

## What it does not include

Koko Meet does not record the call. Moderators can read a report you submit. They cannot watch a recording. Gender and country can be saved on the account. Those preferences do not change who you meet until that option is turned on.

Read [Features](/features), [Safety](/safety), and [Privacy](/privacy) for the details that are actually supported.
TXT),
            $this->row('post', 'how-random-video-chat-works', 'How random video chat works', 'How Random Video Chat Works | Koko Meet', 'What happens from Google sign-in to a one-to-one video connection on Koko Meet.', 'How it works', 'getting-started', $this->howPost(), <<<'TXT'
Koko Meet matches two people who are both looking for a conversation. The pair is random. You are not placed into a group room.

## From sign-in to a call

You sign in with Google and, the first time, choose a gender and confirm a country. Those details are stored on the account. They are not used to filter matches.

When you start a search, the app looks for one other available person. If nobody is waiting, you stay in the queue. When someone is found, the browsers negotiate a direct video connection. If a relay server is configured and a direct connection cannot be made, media may pass through that relay for the length of the call. The site still does not store the video.

## Next and end

Next closes the current conversation and starts another search. End leaves the call without starting a new one. Closing the tab also leaves the call. The other person is told the conversation ended.

Koko Meet keeps a match record, such as when a call started or ended. It does not keep a transcript or a recording.
TXT),
        ];
    }

    private function row(string $type, string $slug, string $title, string $seo, string $description, string $crumb, ?string $category, string $body, ?string $old = null): array
    {
        return compact('type', 'slug', 'title', 'seo', 'description', 'crumb', 'category', 'body') + ['old' => $old, 'seo_title' => $seo];
    }

    private function pages(): array
    {
        return [
            $this->row('page', 'random-video-chat-india', 'Random Video Chat in India – Meet New People Online', 'Random Video Chat in India | Koko Meet', 'Meet new people in India through random one-to-one video chat. Start a browser-based conversation on Koko Meet.', 'India', null, $this->india()),
            $this->row('page', 'video-chat-with-strangers', 'Video Chat with Strangers Online', 'Video Chat with Strangers Online in India | Koko Meet', 'Talk face-to-face with new people through one-to-one random video chat. Start a conversation on Koko Meet.', 'Video chat with strangers', null, $this->videoStrangers()),
            $this->row('page', 'one-to-one-video-chat', 'One-to-One Video Chat – Talk Face-to-Face Online', 'One-to-One Video Chat in India | Koko Meet', 'Start a one-to-one video conversation with another available person. Meet new people through Koko Meet.', 'One-to-one', null, $this->oneToOne()),
            $this->row('page', 'talk-to-girls-online', 'Looking to Talk to Girls Online? Meet New People on Koko Meet', 'Talk to Girls Online – Meet New People | Koko Meet', 'Looking to meet new people? Start a random one-to-one video conversation on Koko Meet. Gender is not guaranteed by matching.', 'Talk to girls online', null, $this->girls()),
            $this->row('page', 'meet-new-people-online', 'Meet New People Online in India', 'Meet New People Online in India | Koko Meet', 'Discover new conversations and meet people online through one-to-one random video chat on Koko Meet.', 'Meet new people', null, $this->meet()),
            $this->row('page', 'make-friends-online', 'Meet New People and Make Friends Online in India', 'Make Friends Online in India – Start a Conversation | Koko Meet', 'Looking to meet people and make new connections? Start a one-to-one video conversation on Koko Meet.', 'Make friends', null, $this->friends()),
            $this->row('page', 'free-video-chat', 'Online Video Chat with New People in India', 'Free Video Chat Online in India | Koko Meet', 'Explore one-to-one video conversations with new people on Koko Meet. Check current service conditions and start a conversation.', 'Video chat', null, $this->free()),
            $this->row('page', 'ometv-alternative', 'Explore an Alternative to OmeTV in India', 'OmeTV Alternative in India – Koko Meet', 'Explore Koko Meet, an independent one-to-one random video chat platform for people in India.', 'OmeTV alternative', null, $this->ometv()),
        ];
    }

    private function about(): string
    {
        return <<<'TXT'
Koko Meet is a browser-based random one-to-one video chat for adults in India. You sign in with Google, complete the required profile, and can be paired with one other available person.

There is no public group room, no text-only stranger chat, and no friend list. Video and audio are not stored. When the network allows it, media travels directly between the two browsers. If a TURN relay is configured and a direct connection cannot be made, the relay may carry media only while the call lasts.

## What a call includes

You can mute the microphone, send text during the match, use Next to look for another person, or End the call. You can choose a camera or microphone from the menu when the browser lists them. Block and Report are available.

## What matching does not do

Matching is random. Koko Meet does not currently guarantee a match by gender, city, state, country, or language. Saved gender and country preferences do not currently affect matching. The product does not verify profiles.

Read [How Koko Meet works](/random-video-chat), [Safety](/safety), and [Privacy](/privacy) before you start. [Sign in](/login) when you want to enter the queue.
TXT;
    }

    private function safety(): string
    {
        return <<<'TXT'
Koko Meet is intended for adults aged 18 and above. The application does not currently verify age with a date of birth or an identity document. Do not use it if you are under 18.

Never share your full name, home address, phone number, school, workplace, passwords, bank details, financial information, or other private accounts with someone you just met.

## Leave, block, or report

Leave a call that feels uncomfortable. Next ends the current match and searches for another person. End leaves without a new search. Block is there to stop a future pairing with that account. Report sends the reason you write to moderators.

Moderators can review the report text and permitted account history. They do not receive the chat transcript, and they cannot watch a recording, because the application does not store video or audio.

## What is not allowed

Harassment, threats, spam, scams, and sexual content involving anyone under 18 are not allowed. Do not record, screenshot, redistribute, or threaten to distribute another participant's image or video without consent.

The contact email is not an emergency service. If you are in immediate danger, contact local emergency services. The community rules are on [Guidelines](/guidelines). Data handling is on [Privacy](/privacy).
TXT;
    }

    private function contact(): string
    {
        return <<<'TXT'
Use the contact email published in the footer when the site operator has added one. That address is for account, safety, and site questions.

It is not a live emergency service. During a call, leave and use Report or Block. If you are in immediate danger, contact local emergency services.

[Safety](/safety) and [Community Guidelines](/guidelines) explain what to do before you write in.
TXT;
    }

    private function features(): string
    {
        return <<<'TXT'
These are the controls a Koko Meet call actually has.

## On a match

Random matching pairs you with one available person. Two video panels show you and the other person. Your own preview is mirrored. Their picture is not. Live video and audio use the browser. Text chat works during that match, not as a separate stranger-text product.

Mute turns the microphone off. Next leaves the current match and searches again. The call asks you to confirm Next while you are connected. End leaves without starting another search. Block and Report are in the menu.

## Camera and microphone

The browser asks for the camera and microphone when you open the call, not on the public pages. You can pick a camera or microphone from the menu when the browser lists more than one.

## Preferences

You can save a gender and a country preference. Those saved preferences do not currently change matching. Blocks and bans still apply.

[Sign in](/login) to start. [Safety](/safety), [Privacy](/privacy), and [Help](/help) cover leaving a call and a camera that will not start.
TXT;
    }

    private function help(): string
    {
        return <<<'TXT'
Most failed calls are a blocked camera, a blocked microphone, or a network that cannot connect the two browsers.

## Permissions

Use a current browser on desktop or mobile. When the call page opens, allow the camera and microphone if you want to be seen and heard. If you blocked them earlier, change the permission in the browser site settings and reload. You can choose another camera or microphone from the call menu.

## Queue and connection

The status line says whether Koko Meet is still looking or connecting. If nobody is free, you remain in the queue. If the other person leaves, the call ends. When a direct connection cannot be made and a TURN relay is configured, media may pass through that relay for the length of the call. Koko Meet still does not store the video or audio.

## Next, End, and a bad conversation

Use Next to leave and search again, or End to leave without a new search. Block and Report are for someone who should not be paired with you again or who broke the [guidelines](/guidelines).

[Safety](/safety) and [Privacy](/privacy) explain what is stored. [Sign in](/login) when you want to try again.
TXT;
    }

    private function randomVideo(): string
    {
        return <<<'TXT'
Looking for a random video chat in India? Koko Meet lets you connect with one available person for a spontaneous one-to-one conversation. [Sign in](/login), enter the matching queue, and start talking when a match is available.

## What is random video chat?

Random video chat connects two people who are looking for a conversation without choosing each other beforehand. Instead of browsing profiles or joining a group room, users are paired with one available person.

## How random video chat works

Sign in with Google. Complete the required profile information. Enter the matching queue. Wait for another available person. Start a live video and text conversation.

## Random video chat for people in India

You may meet people from different backgrounds and interests. Matching is random and does not guarantee a particular city, state, country, gender, or language. Saved preferences do not currently change that.

## Features

One-to-one matching, live video, text chat during a call, microphone mute, camera and microphone selection, Next, End, Block, and Report.

## Stay safe

Do not share contact details, addresses, passwords, financial information, or other sensitive data. Leave uncomfortable calls and use the safety controls. Read [Safety](/safety).

Is random video chat available in India? Yes, subject to service availability and applicable requirements. Can you select a person? No. Can you skip a match? Yes, use Next. Does Koko Meet record calls? No video or audio recordings are stored. Can you use a mobile browser? Yes, if the browser supports the camera and microphone and you grant those permissions.

More pages: [Random video chat in India](/random-video-chat-india), [One-to-one video chat](/one-to-one-video-chat), and [Talk to strangers](/talk-to-strangers).

[Start Random Video Chat](/login)
TXT;
    }

    private function strangers(): string
    {
        return <<<'TXT'
Sometimes you want a conversation with someone new. Koko Meet connects you with one available person through random one-to-one video chat. It is not anonymous: Google sign-in and a profile are required.

## Why talk to someone new?

A short conversation can cover interests, music, films, travel, sport, food, or ordinary life. It does not have to become a friendship, and Koko Meet has no friend-request feature.

## How to start

[Sign in](/login), complete your profile, enter matching, connect, and start talking. If nobody is waiting, you stay in the queue.

## Conversation starters

Which city are you from? What kind of music do you enjoy? What is your favourite film? Which place in India would you like to visit? What do you enjoy doing in your free time? More ideas are on [Conversation starters](/conversation-starters).

## Your choice to continue or leave

Use Next to search for another match, End to leave, or Block and Report when necessary. You may meet someone in India, but matching is random and not geographically guaranteed.

Text chat is available during the video conversation, not as a separate text-only product. Calls are not stored as video or audio. Read [Safety](/safety).

[Start Talking](/login)
TXT;
    }

    private function omegle(): string
    {
        return <<<'TXT'
If you are looking for a way to meet new people through live video, Koko Meet offers random one-to-one video chat in the browser. It is an independent product. It is not Omegle, and it does not claim to work the same way.

## What people mean by an alternative

They usually mean a place to start a conversation with someone they have not chosen. On Koko Meet that means one available person, after Google sign-in and a profile.

## What Koko Meet offers

Random one-to-one matching, live video and audio, text chat during the call, microphone mute, Next, End, Block, and Report. There is no group room and no text-only stranger chat.

## How it is different

Koko Meet has its own sign-in, matching, privacy, and safety systems. Matching is random. Gender, city, state, country, and language are not guaranteed. The application does not store video or audio recordings. A configured relay may carry media during the call only.

Read [Features](/features), [Safety](/safety), and [Privacy](/privacy). Koko Meet is not a replacement that copies another product's rules or promises.

[Try Koko Meet](/login)
TXT;
    }

    private function howPost(): string
    {
        return <<<'TXT'
Koko Meet pairs two people who are both looking for a conversation. The pair is random. You are not placed in a group room, and you do not browse profiles.

## From sign-in to a call

You sign in with Google and complete the required profile, including gender and country. Those details are stored on the account. They are not used to filter matches.

When you start a search, the app looks for one other available person. If nobody is waiting, you stay in the queue. When someone is found, the browsers negotiate a video connection. If a relay is configured and a direct connection cannot be made, media may pass through that relay for the length of the call. The site does not store the video or audio.

## Next, End, and what is kept

Next closes the current conversation and starts another search. End leaves without a new search. Koko Meet keeps a match record, such as when a call started or ended. Text chat from the match is stored with that match and deleted on the retention schedule, currently 90 days. Moderators do not receive that transcript with a report.

The product is marketed for adults in India. Matching is still random, so a partner in a particular city or language is not guaranteed. Read [Random video chat](/random-video-chat) and [Privacy](/privacy).
TXT;
    }

    private function india(): string
    {
        return <<<'TXT'
Want to meet new people online in India? Koko Meet offers random one-to-one video conversations through a supported browser. [Sign in](/login), enter the matching queue, and connect when another person is available.

## Discover new conversations across India

India has different languages, cultures, interests, and experiences. A conversation can introduce a different perspective. That is a possibility, not a filter. Koko Meet does not match you to a state, city, or language.

## How to start

Sign in with Google, complete your profile, enter matching, connect with an available person, and start talking.

## Conversation ideas

Films, music, cricket, food, travel, college, work, hobbies, and something specific to the place you know. Questions are on [Conversation starters](/conversation-starters).

## Can I meet people from my own state?

Matches are random. Koko Meet does not currently guarantee state-, city-, or country-based matching. Hindi-speaking matches are not guaranteed either. You can still talk in a language you both understand.

## Safety and privacy

Do not share sensitive information. Leave uncomfortable conversations and use Block or Report. Details are on [Safety](/safety) and [Privacy](/privacy).

Koko Meet is available in a browser, including a supported mobile browser. You can leave a call whenever you want.

[Start Video Chat](/login)
TXT;
    }

    private function videoStrangers(): string
    {
        return <<<'TXT'
Want to talk to someone new? Koko Meet connects signed-in users with one available person for a random one-to-one video conversation.

## A simple way to start

You do not choose a profile or join a public room. Enter the matching queue and connect when another person is available. If the queue is empty, you wait.

## What you can do

Live video, text chat during the call, microphone mute, Next, End, Block, and Report. Camera and microphone selection is in the call menu when the browser provides a list.

## Be respectful

Do not share sensitive information. Respect a request to stop. Leave if you are uncomfortable. [Safety](/safety) explains the controls. Sign-in is required, so this is not an anonymous chat. See [Talk to strangers](/talk-to-strangers).

The application does not store video or audio recordings. Text chat during the match is kept with the match and then deleted on the retention schedule.

[Start a Conversation](/login)
TXT;
    }

    private function oneToOne(): string
    {
        return <<<'TXT'
Koko Meet focuses on individual conversations rather than public group rooms. Connect with one available participant through live video and text chat.

## One conversation at a time

When a match is available, two participants connect. Talk, use text chat, and decide whether to continue. A third person is not added to the call.

## How it works

Google sign-in, profile setup, the matching queue, a live connection, and the call controls. Matching is random. It is not currently based on gender.

## Available controls

Mute, Next, End, Block, and Report are on the call. You can send messages during an active match. You can end the call. A supported browser is enough. There is no separate app to install.

You cannot choose a specific person. Read [Features](/features) and [Safety](/safety).

[Start One-to-One Video Chat](/login)
TXT;
    }

    private function girls(): string
    {
        return <<<'TXT'
Want to meet someone new and have a conversation? Koko Meet connects available people through random one-to-one video chat. Start a conversation, exchange ideas, and discover different personalities.

## Meet new people through live video

You connect with one available person. The call has live video and text chat. There is no public list of people waiting, and this page does not show profiles.

## Can you video chat with girls on Koko Meet?

Koko Meet uses random matching. You may connect with people of different genders, but there is currently no guarantee that a match will be female. Gender preferences can be saved in the profile but do not currently control matching. Not every user is a woman. Koko Meet is not a dating app.

## What you can talk about

Music, films, travel, hobbies, food, culture, studies, work, and ordinary life. Ideas are on [Conversation starters](/conversation-starters). The product page for the call itself is [Random video chat](/random-video-chat).

## Respect and safety

Respect boundaries. Never pressure anyone to share personal information or to stay on a call. Leave uncomfortable conversations and use Block or Report. Read [Safety](/safety).

You cannot currently choose to talk only to girls. You can skip a match with Next. The service is intended for adults aged 18 and above. The application does not currently verify age with a date of birth.

[Start Random Video Chat](/login)
TXT;
    }

    private function meet(): string
    {
        return <<<'TXT'
Meeting new people can introduce you to different interests, cultures, and perspectives. Koko Meet offers a way to connect with another available person through random one-to-one video chat.

## Discover conversations outside your circle

Talk about hobbies, entertainment, travel, sport, music, and everyday life. A conversation can stay light.

## Start with a simple hello

Ask about a film, a place someone wants to visit, or a hobby. More prompts are on [Conversation starters](/conversation-starters).

## How Koko Meet helps you connect

Random one-to-one matching, live video, text chat during calls, call controls, and safety tools. You may meet people from different cities. City matching is not guaranteed.

## Be respectful

Do not share sensitive information. Koko Meet is not a dating site, and it does not guarantee a friendship. There is no dedicated friend-connection feature. Read [Safety](/safety).

[Meet Someone New](/login)
TXT;
    }

    private function friends(): string
    {
        return <<<'TXT'
Making a new connection can begin with a simple conversation. Koko Meet lets adults connect with another available person through random one-to-one video chat.

## Start with shared interests

Talk about music, films, travel, food, sport, books, hobbies, and everyday experiences. That is a way to talk, not a match setting.

## A conversation is only the beginning

A random conversation does not guarantee friendship. Be respectful, take your time, and protect your personal information. You can exchange contact details only if you choose to, and you should not share anything sensitive with someone you just met. Koko Meet will not keep you connected after the call. There is no friend list.

## Start a conversation

[Sign in](/login), complete your profile, and enter the matching queue. You can leave whenever you want. This is not a dating app.

Read [Meet new people](/meet-new-people-online) and [Safety](/safety).

[Start a Conversation](/login)
TXT;
    }

    private function free(): string
    {
        return <<<'TXT'
Koko Meet offers browser-based one-to-one video conversations. [Sign in](/login), enter matching, and connect with another available person when someone is waiting.

## What is included

Random matching, live video, text chat during a call, microphone mute, camera and microphone selection, Next, End, Block, and Report. There is no group room and no separate text-only chat.

## Is there a charge?

Koko Meet does not charge a fee to sign in or start a call. The application has no paid plan and no usage meter. Your own mobile data, Wi-Fi, and device costs are separate. A conversation still depends on another person being available. "Free" here does not mean unlimited people are waiting, and it does not mean you can use the service without signing in.

## How to start

Sign in with Google, complete the required profile, enter matching, and wait. A supported mobile browser can be used if it can access the camera and microphone.

You can talk to strangers through that random one-to-one match. Read [Random video chat](/random-video-chat) and [Safety](/safety).

[Start Video Chat](/login)
TXT;
    }

    private function ometv(): string
    {
        return <<<'TXT'
Koko Meet offers browser-based random one-to-one video conversations for adults looking to meet new people. It is a separate product from OmeTV. This page does not claim that one product is better, and it does not invent limits for any other service.

## One-to-one random conversations

You [sign in](/login) with Google, complete a profile, and enter a queue. When another person is available, you connect with live video, audio, and text chat for that match. Mute, Next, End, Block, and Report are available. You cannot currently choose gender. Matching is random.

## Safety and privacy

You can report abuse through the report flow. Moderators review the report and permitted account history. They do not get a recording. Koko Meet does not store video or audio. A configured relay may carry media only during the call. Text from the match is kept for the retention period described in [Privacy](/privacy).

Read [Safety](/safety) and [Features](/features) for what this product actually does.

[Start a Conversation](/login)
TXT;
    }
}
