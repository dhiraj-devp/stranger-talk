<?php

namespace App\Http\Controllers;

use App\Models\MatchPreference;
use App\Models\User;
use App\Services\CountrySuggester;
use App\Support\Countries;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function setup(Request $request, CountrySuggester $countries): View|RedirectResponse
    {
        if ($request->user()->profileComplete()) {
            return redirect()->route('home');
        }

        return view('profile.setup', [
            'suggested' => $countries->suggest($request),
            'countries' => Countries::all(),
        ]);
    }

    public function storeSetup(Request $request): RedirectResponse
    {
        $data = $this->profileData($request);
        $request->user()->forceFill([
            'gender' => $data['gender'],
            'country_code' => $data['country_code'],
        ])->save();

        MatchPreference::query()->firstOrCreate(
            ['user_id' => $request->user()->id],
            ['gender_preference' => MatchPreference::ANYONE, 'country_preference' => null]
        );

        return redirect()->route('home');
    }

    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'preference' => $this->preference($request->user()),
            'countries' => Countries::all(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'avatar' => ['nullable', 'string', 'max:2048', 'starts_with:https://'],
            'gender' => ['required', Rule::in(User::GENDERS)],
            'country_code' => ['required', Rule::in(array_keys(Countries::all()))],
            'gender_preference' => ['required', Rule::in(MatchPreference::GENDERS)],
            'country_preference' => ['nullable', Rule::in(array_keys(Countries::all()))],
        ]);

        $user = $request->user();
        $user->forceFill([
            'name' => $data['name'],
            'gender' => $data['gender'],
            'country_code' => $data['country_code'],
            'avatar' => $data['avatar'] ?: $user->avatar,
        ])->save();

        $this->savePreference($user, $data['gender_preference'], $data['country_preference'] ?? null);

        return back()->with('status', 'Profile saved.');
    }

    public function updatePreferences(Request $request): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'gender_preference' => ['required', Rule::in(MatchPreference::GENDERS)],
            'country_preference' => ['nullable', Rule::in(array_merge(['any'], array_keys(Countries::all())))],
        ]);

        $country = $data['country_preference'] ?? null;
        if ($country === 'any' || $country === '') {
            $country = null;
        }

        $preference = $this->savePreference($request->user(), $data['gender_preference'], $country);

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'summary' => $preference->summary()]);
        }

        return back()->with('status', 'Preferences saved.');
    }

    private function profileData(Request $request): array
    {
        return $request->validate([
            'gender' => ['required', Rule::in(User::GENDERS)],
            'country_code' => ['required', Rule::in(array_keys(Countries::all()))],
        ]);
    }

    private function preference(User $user): MatchPreference
    {
        return MatchPreference::query()->firstOrCreate(
            ['user_id' => $user->id],
            ['gender_preference' => MatchPreference::ANYONE, 'country_preference' => null]
        );
    }

    private function savePreference(User $user, string $gender, ?string $country): MatchPreference
    {
        return MatchPreference::query()->updateOrCreate(
            ['user_id' => $user->id],
            ['gender_preference' => $gender, 'country_preference' => $country]
        );
    }
}
