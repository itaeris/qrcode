<?php

namespace Tests\Feature;

use App\Models\User;
use App\Qr\QrInput;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AccountCodeOwnershipTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function each_account_keeps_its_own_codes(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        $created = $this->actingAs($owner)->postJson('/qr', [
            'type' => 'url',
            'title' => 'Owned QR',
            'fields' => ['url' => 'https://example.com/owned'],
            'style' => QrInput::defaultStyle(),
        ])->assertOk();

        $code = $created->json('code');

        $this->actingAs($owner)->post('/links', [
            'url' => 'https://example.com/link',
            'title' => 'Owned link',
            'code' => 'owned1',
        ])->assertRedirect('/links');

        $this->actingAs($other)->get('/')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('projects', 0));

        $this->actingAs($other)->get('/links')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('links', 0));

        $this->actingAs($other)->get('/manage/'.$code)->assertNotFound();
        $this->actingAs($other)->get('/manage/'.$code.'/edit')->assertNotFound();
        $this->actingAs($other)->postJson('/qr/'.$code.'/open', [
            'secret' => $created->json('secret'),
        ])->assertNotFound();
        $this->actingAs($other)->postJson('/qr/'.$code.'/delete')->assertNotFound();
        $this->actingAs($other)->get('/links/owned1')->assertNotFound();
        $this->actingAs($other)->put('/links/owned1', [
            'url' => 'https://example.com/taken',
        ])->assertNotFound();
        $this->actingAs($other)->post('/links/owned1/delete')->assertNotFound();

        $this->get('/q/'.$code)->assertRedirect('https://example.com/owned');
        $this->get('/s/owned1')->assertRedirect('https://example.com/link');

        $this->actingAs($owner)->postJson('/qr/'.$code.'/open', ['secret' => ''])->assertOk();
        $this->actingAs($owner)->get('/')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('projects', 1)->where('projects.0.title', 'Owned QR'));
        $this->actingAs($owner)->get('/links')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('links', 1));

        $this->assertDatabaseHas('qr_links', ['code' => $code, 'user_id' => $owner->id]);
        $this->assertDatabaseHas('short_links', ['code' => 'owned1', 'user_id' => $owner->id]);
    }
}
