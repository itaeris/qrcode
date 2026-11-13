<?php

namespace Tests\Feature;

use App\Models\User;
use App\Qr\QrInput;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProjectShareTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function an_owner_can_share_a_project_as_view_or_edit(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create([
            'name' => 'Member',
            'email' => 'member@example.com',
        ]);

        $created = $this->actingAs($owner)->postJson('/qr', [
            'type' => 'url',
            'title' => 'Shared QR',
            'fields' => ['url' => 'https://example.com/shared'],
            'style' => QrInput::defaultStyle(),
        ])->assertOk();
        $code = $created->json('code');

        $this->actingAs($owner)->get('/')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('accounts', 1)
                ->where('accounts.0.email', 'member@example.com')
                ->where('accounts.0.name', 'Member'));

        $this->actingAs($owner)->postJson('/qr/'.$code.'/shares', [
            'email' => 'Member@Example.com',
            'role' => 'view',
        ])->assertOk()->assertJsonPath('shares.0.email', 'member@example.com')->assertJsonPath('shares.0.role', 'view');

        $this->actingAs($owner)->postJson('/qr/'.$code.'/shares', [
            'email' => $owner->email,
            'role' => 'edit',
        ])->assertUnprocessable()->assertJsonValidationErrors('email');

        $this->actingAs($owner)->postJson('/qr/'.$code.'/shares', [
            'email' => 'missing@example.com',
            'role' => 'view',
        ])->assertUnprocessable()->assertJsonValidationErrors('email');

        $this->actingAs($member)->get('/')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('projects', 1)->where('projects.0.access', 'view'));

        $this->actingAs($member)->get('/manage/'.$code)
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('qr.can_edit', false));
        $this->actingAs($member)->get('/manage/'.$code.'/edit')->assertNotFound();
        $this->actingAs($member)->postJson('/qr/'.$code, [
            'type' => 'url',
            'fields' => ['url' => 'https://example.com/nope'],
            'style' => QrInput::defaultStyle(),
        ])->assertNotFound();
        $this->actingAs($member)->postJson('/qr/'.$code.'/delete')->assertNotFound();
        $this->actingAs($member)->postJson('/qr/'.$code.'/shares', [
            'email' => $owner->email,
            'role' => 'edit',
        ])->assertNotFound();

        $this->actingAs($owner)->postJson('/qr/'.$code.'/shares/'.$member->id, [
            'role' => 'edit',
        ])->assertOk()->assertJsonPath('shares.0.role', 'edit');

        $this->actingAs($member)->get('/manage/'.$code.'/edit')->assertOk();
        $this->actingAs($member)->postJson('/qr/'.$code, [
            'type' => 'url',
            'fields' => ['url' => 'https://example.com/edited'],
            'style' => QrInput::defaultStyle(),
        ])->assertOk();
        $this->actingAs($member)->postJson('/qr/'.$code.'/delete')->assertNotFound();
        $this->get('/q/'.$code)->assertRedirect('https://example.com/edited');

        $this->actingAs($owner)->postJson('/qr/'.$code.'/shares/'.$member->id.'/delete')
            ->assertOk()
            ->assertJsonPath('shares', []);

        $this->actingAs($member)->get('/')->assertOk()->assertInertia(fn ($page) => $page->has('projects', 0));
        $this->actingAs($owner)->get('/')->assertOk()->assertInertia(fn ($page) => $page
            ->has('projects', 1)
            ->where('projects.0.access', 'owner'));
    }
}
