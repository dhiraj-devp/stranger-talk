<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use RuntimeException;

class GoogleAccountService
{
    public function authenticate(SocialiteUser $google): User
    {
        $googleId = (string) $google->getId();
        $email = strtolower(trim((string) $google->getEmail()));
        $verified = (bool) data_get($google->user ?? [], 'email_verified', false);

        if ($googleId === '' || $email === '') {
            throw new RuntimeException('Google did not provide an email address.');
        }

        $byGoogle = User::query()->where('google_id', $googleId)->first();
        if ($byGoogle) {
            $this->refreshAvatar($byGoogle, $google->getAvatar());

            return $byGoogle;
        }

        $byEmail = User::query()->where('email', $email)->first();
        if ($byEmail) {
            if ($byEmail->google_id && $byEmail->google_id !== $googleId) {
                throw new RuntimeException('This email is already linked to a different Google account.');
            }

            if (! $byEmail->email_verified_at || ! $verified) {
                throw new RuntimeException('An account with this email already exists and cannot be linked.');
            }

            $byEmail->forceFill([
                'google_id' => $googleId,
                'avatar' => $byEmail->avatar ?: $this->safeAvatar($google->getAvatar()),
            ])->save();

            return $byEmail;
        }

        if (! $verified) {
            throw new RuntimeException('Google did not confirm this email address.');
        }

        $user = User::query()->create([
            'name' => Str::limit((string) ($google->getName() ?: 'Stranger'), 255, ''),
            'email' => $email,
            'password' => Str::random(40),
            'status' => User::ACTIVE,
        ]);

        $user->forceFill([
            'email_verified_at' => now(),
            'google_id' => $googleId,
            'avatar' => $this->safeAvatar($google->getAvatar()),
        ])->save();

        return $user;
    }

    private function refreshAvatar(User $user, ?string $avatar): void
    {
        $safe = $this->safeAvatar($avatar);
        if ($safe && ! $user->avatar) {
            $user->forceFill(['avatar' => $safe])->save();
        }
    }

    private function safeAvatar(?string $avatar): ?string
    {
        if (! is_string($avatar) || ! str_starts_with($avatar, 'https://') || strlen($avatar) > 2048) {
            return null;
        }

        return $avatar;
    }
}
