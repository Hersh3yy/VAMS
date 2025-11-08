<?php

declare(strict_types=1);

use App\Models\Entry;
use App\Models\EntryType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ================================
// HAPPY PATH TESTS - The glorious successes!
// ================================

it('displays the entry index page for authorized users with correct entry type', function () {
    $user = User::factory()->create([
        'entry_type_permissions' => ['i-ams'],
    ]);

    $entryType = EntryType::create([
        'name' => 'I AM',
        'slug' => 'i-ams',
        'description' => 'Affirmations',
        'field_config' => [],
        'is_active' => true,
    ]);

    Entry::create([
        'user_id' => $user->id,
        'entry_type_id' => $entryType->id,
        'title' => 'Test Entry',
        'content' => ['statement' => 'I am testing'],
        'status' => 'published',
        'published_at' => now(),
        'order' => 0,
    ]);

    $response = $this->actingAs($user)->get(route('entries.index', ['type' => 'i-ams']));

    $response->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Entries/Index')
            ->has('entries', 1)
            ->where('entryType.slug', 'i-ams')
        );
});

it('allows authorized users to create a new entry', function () {
    $user = User::factory()->create([
        'entry_type_permissions' => ['i-ams'],
    ]);

    $entryType = EntryType::create([
        'name' => 'I AM',
        'slug' => 'i-ams',
        'description' => 'Affirmations',
        'field_config' => [],
        'is_active' => true,
    ]);

    $response = $this->actingAs($user)->post(route('entries.store'), [
        'title' => 'I am powerful',
        'content' => 'I am powerful and capable',
        'entry_type_id' => $entryType->id,
        'status' => 'published',
    ]);

    $response->assertRedirect(route('entries.index', ['type' => 'i-ams']))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('entries', [
        'user_id' => $user->id,
        'title' => 'I am powerful',
        'status' => 'published',
        'entry_type_id' => $entryType->id,
    ]);
});

it('defaults to published status when status is not provided', function () {
    $user = User::factory()->create([
        'entry_type_permissions' => ['i-ams'],
    ]);

    $entryType = EntryType::create([
        'name' => 'I AM',
        'slug' => 'i-ams',
        'field_config' => [],
        'is_active' => true,
    ]);

    $this->actingAs($user)->post(route('entries.store'), [
        'title' => 'I am awesome',
        'content' => 'I am absolutely awesome',
        'entry_type_id' => $entryType->id,
        // No status provided
    ]);

    $this->assertDatabaseHas('entries', [
        'user_id' => $user->id,
        'title' => 'I am awesome',
        'status' => 'published',
    ]);

    $entry = Entry::where('title', 'I am awesome')->first();
    expect($entry->published_at)->not->toBeNull();
});

it('allows users to update their own entries', function () {
    $user = User::factory()->create([
        'entry_type_permissions' => ['i-ams'],
    ]);

    $entryType = EntryType::create([
        'name' => 'I AM',
        'slug' => 'i-ams',
        'field_config' => [],
        'is_active' => true,
    ]);

    $entry = Entry::create([
        'user_id' => $user->id,
        'entry_type_id' => $entryType->id,
        'title' => 'Original Title',
        'content' => ['statement' => 'Original content'],
        'status' => 'published',
        'published_at' => now(),
        'order' => 0,
    ]);

    $response = $this->actingAs($user)->patch(route('entries.update', $entry->id), [
        'title' => 'Updated Title',
        'content' => 'Updated content',
        'status' => 'published',
    ]);

    $response->assertRedirect(route('entries.index', ['type' => 'i-ams']))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('entries', [
        'id' => $entry->id,
        'title' => 'Updated Title',
    ]);
});

it('allows users to delete their own entries', function () {
    $user = User::factory()->create([
        'entry_type_permissions' => ['i-ams'],
    ]);

    $entryType = EntryType::create([
        'name' => 'I AM',
        'slug' => 'i-ams',
        'field_config' => [],
        'is_active' => true,
    ]);

    $entry = Entry::create([
        'user_id' => $user->id,
        'entry_type_id' => $entryType->id,
        'title' => 'To Delete',
        'content' => ['statement' => 'Delete me'],
        'status' => 'published',
        'published_at' => now(),
        'order' => 0,
    ]);

    $response = $this->actingAs($user)->delete(route('entries.destroy', $entry->id));

    $response->assertRedirect();
    $this->assertDatabaseMissing('entries', [
        'id' => $entry->id,
    ]);
});

it('allows users to reorder their entries', function () {
    $user = User::factory()->create([
        'entry_type_permissions' => ['i-ams'],
    ]);

    $entryType = EntryType::create([
        'name' => 'I AM',
        'slug' => 'i-ams',
        'field_config' => [],
        'is_active' => true,
    ]);

    $entry1 = Entry::create([
        'user_id' => $user->id,
        'entry_type_id' => $entryType->id,
        'title' => 'First',
        'content' => ['statement' => 'First'],
        'status' => 'published',
        'published_at' => now(),
        'order' => 0,
    ]);

    $entry2 = Entry::create([
        'user_id' => $user->id,
        'entry_type_id' => $entryType->id,
        'title' => 'Second',
        'content' => ['statement' => 'Second'],
        'status' => 'published',
        'published_at' => now(),
        'order' => 1,
    ]);

    $response = $this->actingAs($user)->patch(route('entries.reorder'), [
        'orderedIds' => [$entry2->id, $entry1->id],
        'entry_type_id' => $entryType->id,
    ]);

    $response->assertRedirect();

    // Verify order changed
    expect(Entry::find($entry2->id)->order)->toBe(0);
    expect(Entry::find($entry1->id)->order)->toBe(1);
});

// ================================
// SAD PATH TESTS - The dastardly failures!
// ================================

it('prevents unauthorized users from accessing entry types', function () {
    $user = User::factory()->create([
        'entry_type_permissions' => [], // No permissions!
    ]);

    $entryType = EntryType::create([
        'name' => 'I AM',
        'slug' => 'i-ams',
        'field_config' => [],
        'is_active' => true,
    ]);

    $response = $this->actingAs($user)->get(route('entries.index', ['type' => 'i-ams']));

    $response->assertForbidden();
});

it('prevents users from creating entries for unauthorized entry types', function () {
    $user = User::factory()->create([
        'entry_type_permissions' => ['other-type'],
    ]);

    $entryType = EntryType::create([
        'name' => 'I AM',
        'slug' => 'i-ams',
        'field_config' => [],
        'is_active' => true,
    ]);

    $response = $this->actingAs($user)->post(route('entries.store'), [
        'title' => 'Sneaky Entry',
        'content' => 'I should not be able to create this',
        'entry_type_id' => $entryType->id,
        'status' => 'published',
    ]);

    $response->assertRedirect();
    $response->assertSessionHasErrors();

    $this->assertDatabaseMissing('entries', [
        'title' => 'Sneaky Entry',
    ]);
});

it('requires title when creating an entry', function () {
    $user = User::factory()->create([
        'entry_type_permissions' => ['i-ams'],
    ]);

    $entryType = EntryType::create([
        'name' => 'I AM',
        'slug' => 'i-ams',
        'field_config' => [],
        'is_active' => true,
    ]);

    $response = $this->actingAs($user)->post(route('entries.store'), [
        // Missing title!
        'content' => 'I have no title',
        'entry_type_id' => $entryType->id,
    ]);

    $response->assertSessionHasErrors('title');
});

it('requires content when creating an entry', function () {
    $user = User::factory()->create([
        'entry_type_permissions' => ['i-ams'],
    ]);

    $entryType = EntryType::create([
        'name' => 'I AM',
        'slug' => 'i-ams',
        'field_config' => [],
        'is_active' => true,
    ]);

    $response = $this->actingAs($user)->post(route('entries.store'), [
        'title' => 'Empty Entry',
        // Missing content!
        'entry_type_id' => $entryType->id,
    ]);

    $response->assertSessionHasErrors('content');
});

it('requires entry_type_id when creating an entry', function () {
    $user = User::factory()->create([
        'entry_type_permissions' => ['i-ams'],
    ]);

    $response = $this->actingAs($user)->post(route('entries.store'), [
        'title' => 'No Type Entry',
        'content' => 'I have no type',
        // Missing entry_type_id!
    ]);

    $response->assertSessionHasErrors('entry_type_id');
});

it('validates status must be draft or published', function () {
    $user = User::factory()->create([
        'entry_type_permissions' => ['i-ams'],
    ]);

    $entryType = EntryType::create([
        'name' => 'I AM',
        'slug' => 'i-ams',
        'field_config' => [],
        'is_active' => true,
    ]);

    $response = $this->actingAs($user)->post(route('entries.store'), [
        'title' => 'Invalid Status',
        'content' => 'Testing invalid status',
        'entry_type_id' => $entryType->id,
        'status' => 'archived', // Invalid status!
    ]);

    $response->assertSessionHasErrors('status');
});

it('prevents users from updating entries they do not own', function () {
    $user1 = User::factory()->create([
        'entry_type_permissions' => ['i-ams'],
    ]);

    $user2 = User::factory()->create([
        'entry_type_permissions' => ['i-ams'],
    ]);

    $entryType = EntryType::create([
        'name' => 'I AM',
        'slug' => 'i-ams',
        'field_config' => [],
        'is_active' => true,
    ]);

    $entry = Entry::create([
        'user_id' => $user1->id,
        'entry_type_id' => $entryType->id,
        'title' => 'User 1 Entry',
        'content' => ['statement' => 'User 1 content'],
        'status' => 'published',
        'published_at' => now(),
        'order' => 0,
    ]);

    // User2 tries to update User1's entry - the villain!
    $response = $this->actingAs($user2)->patch(route('entries.update', $entry->id), [
        'title' => 'Hacked Title',
        'content' => 'Hacked content',
    ]);

    $response->assertForbidden();

    // Verify entry was NOT updated
    $this->assertDatabaseHas('entries', [
        'id' => $entry->id,
        'title' => 'User 1 Entry',
    ]);
});

it('prevents users from deleting entries they do not own', function () {
    $user1 = User::factory()->create([
        'entry_type_permissions' => ['i-ams'],
    ]);

    $user2 = User::factory()->create([
        'entry_type_permissions' => ['i-ams'],
    ]);

    $entryType = EntryType::create([
        'name' => 'I AM',
        'slug' => 'i-ams',
        'field_config' => [],
        'is_active' => true,
    ]);

    $entry = Entry::create([
        'user_id' => $user1->id,
        'entry_type_id' => $entryType->id,
        'title' => 'Protected Entry',
        'content' => ['statement' => 'Cannot delete me'],
        'status' => 'published',
        'published_at' => now(),
        'order' => 0,
    ]);

    // User2 tries to delete User1's entry - dastardly!
    $response = $this->actingAs($user2)->delete(route('entries.destroy', $entry->id));

    $response->assertForbidden();

    // Verify entry still exists
    $this->assertDatabaseHas('entries', [
        'id' => $entry->id,
    ]);
});

it('returns 404 for non-existent entry types', function () {
    $user = User::factory()->create([
        'entry_type_permissions' => ['non-existent'],
    ]);

    $response = $this->actingAs($user)->get(route('entries.index', ['type' => 'non-existent']));

    $response->assertNotFound();
});

it('requires type parameter for entry index', function () {
    $user = User::factory()->create([
        'entry_type_permissions' => ['i-ams'],
    ]);

    // No type parameter!
    $response = $this->actingAs($user)->get(route('entries.index'));

    $response->assertStatus(400);
});

it('requires type parameter for entry create', function () {
    $user = User::factory()->create([
        'entry_type_permissions' => ['i-ams'],
    ]);

    // No type parameter!
    $response = $this->actingAs($user)->get(route('entries.create'));

    $response->assertStatus(400);
});

it('prevents guests from accessing entries', function () {
    $entryType = EntryType::create([
        'name' => 'I AM',
        'slug' => 'i-ams',
        'field_config' => [],
        'is_active' => true,
    ]);

    // Not authenticated!
    $response = $this->get(route('entries.index', ['type' => 'i-ams']));

    $response->assertRedirect(route('login'));
});

it('prevents reordering entries from different entry types', function () {
    $user = User::factory()->create([
        'entry_type_permissions' => ['i-ams', 'gratitude'],
    ]);

    $type1 = EntryType::create([
        'name' => 'I AM',
        'slug' => 'i-ams',
        'field_config' => [],
        'is_active' => true,
    ]);

    $type2 = EntryType::create([
        'name' => 'Gratitude',
        'slug' => 'gratitude',
        'field_config' => [],
        'is_active' => true,
    ]);

    $entry1 = Entry::create([
        'user_id' => $user->id,
        'entry_type_id' => $type1->id,
        'title' => 'I AM Entry',
        'content' => ['statement' => 'Type 1'],
        'status' => 'published',
        'published_at' => now(),
        'order' => 0,
    ]);

    $entry2 = Entry::create([
        'user_id' => $user->id,
        'entry_type_id' => $type2->id,
        'title' => 'Gratitude Entry',
        'content' => ['statement' => 'Type 2'],
        'status' => 'published',
        'published_at' => now(),
        'order' => 0,
    ]);

    // Try to reorder entries from different types - sneaky!
    $response = $this->actingAs($user)->patch(route('entries.reorder'), [
        'orderedIds' => [$entry1->id, $entry2->id],
        'entry_type_id' => $type1->id,
    ]);

    $response->assertRedirect();
    $response->assertSessionHasErrors();
});

