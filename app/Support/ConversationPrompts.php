<?php

namespace App\Support;

class ConversationPrompts
{
    public static function starters(): array
    {
        return [
            'First conversation' => [
                'What did you do earlier today?',
                'What is one thing you are looking forward to this week?',
                'How do you usually spend a free evening?',
                'What is a small habit you enjoy?',
                'Are you someone who talks a lot, or do you like a quieter conversation?',
            ],
            'Funny' => [
                'What is a harmless opinion you will defend anyway?',
                'Which everyday chore would you happily never do again?',
                'What is the most useless talent you have?',
                'If your week had a title, what would it be?',
                'What snack do you take more seriously than you should?',
            ],
            'Music' => [
                'What song have you played more than once this week?',
                'Do you listen to music while travelling, or do you prefer quiet?',
                'Which concert or live show would you still like to attend?',
                'Is there a film song you know even when you do not know the film?',
                'What kind of music fits a late-night drive?',
            ],
            'Movies' => [
                'Which film could you watch again without checking the time?',
                'Do you prefer a story that ends neatly, or one that leaves a question?',
                'Which actor or director do you follow?',
                'What is a film people love that you have not seen yet?',
                'Is there a scene you still quote with friends?',
            ],
            'Travel' => [
                'Which place in India would you like to visit next?',
                'Do you prefer a city trip or somewhere quieter?',
                'What is a train, bus, or road journey you remember?',
                'Hill station, coast, or a city you already know well?',
                'What do you always carry when you travel?',
            ],
            'Food' => [
                'What is a meal you could eat every week?',
                'Sweet, spicy, or something in between?',
                'Is there a street food you will cross the road for?',
                'Do you cook, or do you know exactly where to order from?',
                'What is a festival dish you wait for?',
            ],
            'Hobbies' => [
                'What do you do when you have an hour with no plan?',
                'Are you following a sport this season?',
                'What hobby did you start and then abandon?',
                'Do you read, play games, or watch something to unwind?',
                'Is there a skill you would like to be casually good at?',
            ],
            'General' => [
                'What is a view you changed your mind about?',
                'Morning person, or does the day start later for you?',
                'What is something ordinary that you enjoy more than most people?',
                'Which subject in school turned out to be useful later?',
                'What is a kind thing a stranger once did for you?',
            ],
            'Hindi' => [
                'Aaj ka din kaisa raha? (How has your day been?)',
                'Aap gana sunte waqt kaun si bhasha pasand karte hain? (Which language do you like for songs?)',
                'India mein aap agli chhutti kahan bitana chahenge? (Where in India would you spend your next day off?)',
                'Aapko kaun sa khana ghar jaisa lagta hai? (Which food feels like home?)',
                'Shaam ko aap aksar kya karte hain? (What do you usually do in the evening?)',
            ],
        ];
    }

    public static function questions(): array
    {
        return [
            'Funny' => self::starters()['Funny'],
            'Interesting' => [
                'What is a place in your city that visitors usually miss?',
                'Which language do you wish you could speak a little better?',
                'What is a tradition from your region that you still like?',
                'If you could learn one practical skill this year, what would it be?',
                'What is a story your family tells too often?',
            ],
            'First conversation' => self::starters()['First conversation'],
            'Travel' => self::starters()['Travel'],
            'Movies' => self::starters()['Movies'],
            'Music' => self::starters()['Music'],
            'Food' => self::starters()['Food'],
            'Hobbies' => self::starters()['Hobbies'],
            'General' => self::starters()['General'],
            'Hindi' => self::starters()['Hindi'],
        ];
    }

    public static function rather(): array
    {
        return [
            'Everyday' => [
                'Would you rather have a quiet morning or a long evening with friends?',
                'Would you rather cook a simple meal or spend that time on a walk?',
                'Would you rather plan a day or decide as you go?',
                'Would you rather live near a railway station or near a park?',
            ],
            'Travel' => [
                'Would you rather take a night train or an early flight?',
                'Would you rather revisit a city you know or go somewhere new in India?',
                'Would you rather spend a day at the coast or in the hills?',
                'Would you rather travel with a list or with no fixed plan?',
            ],
            'Food and culture' => [
                'Would you rather eat something familiar or try a dish from another state?',
                'Would you rather watch a new film or a match you care about?',
                'Would you rather listen to an old song or something released this month?',
                'Would you rather celebrate a festival at home or in a crowded street?',
            ],
        ];
    }

    public static function topics(): array
    {
        return [
            'Travel' => ['A journey in India that took longer than planned', 'A city that feels different at night', 'The best window seat you have had'],
            'Music' => ['A song that reminds you of college or school', 'Live music versus a good pair of earphones', 'A playlist for a rainy evening'],
            'Food' => ['A breakfast you miss when you travel', 'Street food you trust', 'A recipe nobody in your house agrees on'],
            'Films' => ['A film that got better the second time', 'Interval snacks', 'A character you understood only later'],
            'Sport' => ['A cricket match you still remember', 'Playing a sport versus watching it', 'A local game from your neighbourhood'],
            'Technology' => ['A phone habit you would like to drop', 'The last useful thing you learned online', 'Maps, before and after smartphones'],
            'Culture' => ['A festival you explain to someone from another state', 'A word that does not translate neatly', 'Clothes you wear only for one occasion'],
            'Everyday' => ['A commute you know by heart', 'Sunday with no plan', 'A shopkeeper who knows your order'],
        ];
    }

    public static function payload(array $groups, bool $topics = false): array
    {
        $flat = [];
        foreach ($groups as $items) {
            foreach ($items as $item) {
                $flat[] = $item;
            }
        }

        return [
            'groups' => $groups,
            'flat' => $flat,
            'topics' => $topics,
        ];
    }
}
