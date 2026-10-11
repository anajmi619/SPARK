<?php

namespace App\Livewire\Settings;

use App\Concerns\ProfileValidationRules;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Tetapan Profil')]
#[Layout('layouts::settings')]
class Profile extends Component
{
    use ProfileValidationRules;

    public string $full_name = '';

    public string $username = '';

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->full_name = Auth::user()->full_name;
        $this->username = Auth::user()->username;
    }

    /**
     * Update the profile information for the currently authenticated user.
     */
    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $validated = $this->validate($this->profileRules($user->getKey()));

        $user->fill($validated);

        $user->save();

        Flux::toast(variant: 'success', text: __('Profil berjaya dikemas kini.'));
    }
}
