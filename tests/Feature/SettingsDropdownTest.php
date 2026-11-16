<?php

use App\Livewire\SettingsDropdown;
use App\Models\User;
use App\Services\ChangelogService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;

it('loads changelog entries only while the modal is open', function (string $trigger) {
    $user = new User(['email' => 'test@example.com']);
    $user->id = 1;

    Auth::setUser($user);

    $service = new class extends ChangelogService
    {
        public int $entryLoads = 0;

        public function getEntriesForUser(User $user): Collection
        {
            $this->entryLoads++;

            return collect([
                (object) [
                    'tag_name' => 'v1.0.0',
                    'title' => 'Test Release',
                    'content' => 'Release notes',
                    'content_html' => '<p>Release notes</p>',
                    'published_at' => Carbon::parse('2026-05-01'),
                    'is_read' => false,
                ],
            ]);
        }

        public function getUnreadCountForUser(User $user): int
        {
            return 1;
        }
    };
    app()->instance(ChangelogService::class, $service);

    $component = Livewire::test(SettingsDropdown::class, ['trigger' => $trigger])
        ->assertSee('1 unread changelog entry')
        ->assertDontSee('Test Release');

    expect($service->entryLoads)->toBe(0);

    $component->call('openWhatsNewModal')
        ->assertSet('showWhatsNewModal', true)
        ->assertSee('Test Release');

    expect($service->entryLoads)->toBe(1);

    $component->call('closeWhatsNewModal')
        ->assertSet('showWhatsNewModal', false)
        ->assertDontSee('Test Release');

    expect($service->entryLoads)->toBe(1);

    $component->call('openWhatsNewModal')
        ->assertSee('Test Release');

    expect($service->entryLoads)->toBe(2);
})->with(['changelog-sidebar', 'account-menu']);

it('opens the changelog modal when there are no entries', function () {
    $user = new User(['email' => 'test@example.com']);
    $user->id = 1;

    Auth::setUser($user);

    app()->instance(ChangelogService::class, new class extends ChangelogService
    {
        public function getEntriesForUser(User $user): Collection
        {
            return collect();
        }

        public function getUnreadCountForUser(User $user): int
        {
            return 0;
        }
    });

    Livewire::test(SettingsDropdown::class, ['trigger' => 'account-menu'])
        ->call('openWhatsNewModal')
        ->assertSet('showWhatsNewModal', true);
});
