<?php

namespace App\Http\Controllers;

use App\Services\SeoBuilder;
use App\Support\ConversationPrompts;
use Illuminate\View\View;

class ToolController extends Controller
{
    public function __construct(private SeoBuilder $seo) {}

    public function starters(): View
    {
        return $this->show(
            'starters',
            'Conversation Starters for Meeting Someone New',
            'Conversation Starters – Questions to Ask Someone New | Koko Meet',
            'Find conversation starters and interesting questions to help break the ice and start a friendly conversation.',
            '/conversation-starters',
            'Not sure what to say when you meet someone new? Explore simple questions and conversation ideas that can help start a friendly discussion. These prompts are for a conversation on Koko Meet or anywhere else. They are not a matching filter.',
            ConversationPrompts::payload(ConversationPrompts::starters()),
            'Show another question',
            '/random-video-chat'
        );
    }

    public function questions(): View
    {
        return $this->show(
            'questions',
            'Random Question Generator – Find Something to Talk About',
            'Random Question Generator – Conversation Questions | Koko Meet',
            'Generate interesting random questions to break the ice and start better conversations.',
            '/random-question-generator',
            'Pick a category and draw another question. The list is on this page, so you can read it without an account. Nothing here is saved.',
            ConversationPrompts::payload(ConversationPrompts::questions()),
            'Next question',
            '/talk-to-strangers'
        );
    }

    public function rather(): View
    {
        return $this->show(
            'rather',
            'Would You Rather – Fun Questions for Conversations',
            'Would You Rather Questions – Fun Conversation Game | Koko Meet',
            'Explore fun would-you-rather questions for friendly conversations and icebreakers.',
            '/would-you-rather',
            'Use these as icebreakers in a conversation. Koko Meet does not include this as a game inside the call. Keep the questions light, and leave any conversation that stops being comfortable.',
            ConversationPrompts::payload(ConversationPrompts::rather()),
            'Another question',
            '/one-to-one-video-chat'
        );
    }

    public function topics(): View
    {
        return $this->show(
            'topics',
            'Random Topic Generator – Find Something Interesting to Discuss',
            'Random Topic Generator – Find Conversation Ideas | Koko Meet',
            'Generate a random topic to help start an interesting conversation.',
            '/random-topic-generator',
            'Draw a topic when a conversation needs a starting point. Topics are ideas, not a promise about who you will meet.',
            ConversationPrompts::payload(ConversationPrompts::topics(), true),
            'New topic',
            '/meet-new-people-online'
        );
    }

    private function show(string $mode, string $heading, string $title, string $description, string $path, string $intro, array $payload, string $button, string $cta): View
    {
        return view('tools.prompts', [
            'mode' => $mode,
            'heading' => $heading,
            'intro' => $intro,
            'groups' => $payload['groups'],
            'button' => $button,
            'cta' => $cta,
            'payload' => $payload,
            'seo' => $this->seo->page($heading, $description, $path, $title),
        ]);
    }
}
