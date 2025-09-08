<?php

declare(strict_types=1);

use App\Models\Activity;
use App\Models\User;
use App\Models\Album;
use Mockery;

it('has correct fillable attributes', function () {
    $activity = new Activity();
    $fillable = $activity->getFillable();
    
    expect($fillable)->toContain('type');
    expect($fillable)->toContain('description');
    expect($fillable)->toContain('user_id');
    expect($fillable)->toContain('subject_type');
    expect($fillable)->toContain('subject_id');
    expect($fillable)->toContain('properties');
});

it('casts properties to array', function () {
    $activity = new Activity();
    $casts = $activity->getCasts();
    
    expect($casts['properties'])->toBe('array');
    expect($casts['user_id'])->toBe('string');
});

it('has user relationship method', function () {
    $activity = new Activity();
    
    // Check that the method exists and is callable
    expect(method_exists($activity, 'user'))->toBeTrue();
    expect(is_callable([$activity, 'user']))->toBeTrue();
});

it('has subject relationship method', function () {
    $activity = new Activity();
    
    // Check that the method exists and is callable
    expect(method_exists($activity, 'subject'))->toBeTrue();
    expect(is_callable([$activity, 'subject']))->toBeTrue();
});

it('can be created with minimal data', function () {
    $activity = new Activity([
        'type' => 'test',
        'description' => 'Test activity',
        'user_id' => '123e4567-e89b-12d3-a456-426614174000',
        'subject_type' => User::class,
        'subject_id' => '123e4567-e89b-12d3-a456-426614174000',
    ]);
    
    expect($activity->type)->toBe('test');
    expect($activity->description)->toBe('Test activity');
    expect($activity->user_id)->toBe('123e4567-e89b-12d3-a456-426614174000');
    expect($activity->subject_type)->toBe(User::class);
    expect($activity->subject_id)->toBe('123e4567-e89b-12d3-a456-426614174000');
});

it('can be created with properties', function () {
    $properties = [
        'ip_address' => '192.168.1.1',
        'user_agent' => 'Mozilla/5.0',
        'changes' => ['old' => 'value1', 'new' => 'value2']
    ];
    
    $activity = new Activity([
        'type' => 'update',
        'description' => 'Updated something',
        'user_id' => '123e4567-e89b-12d3-a456-426614174000',
        'subject_type' => Album::class,
        'subject_id' => '456e7890-e89b-12d3-a456-426614174001',
        'properties' => $properties,
    ]);
    
    expect($activity->properties)->toBe($properties);
    expect($activity->properties['ip_address'])->toBe('192.168.1.1');
    expect($activity->properties['changes']['old'])->toBe('value1');
});

it('can access user through relationship', function () {
    $user = Mockery::mock(User::class);
    $user->shouldReceive('getAttribute')
        ->with('id')
        ->andReturn('123e4567-e89b-12d3-a456-426614174000');
    $user->shouldReceive('getAttribute')
        ->with('name')
        ->andReturn('John Doe');
    
    $activity = new Activity([
        'user_id' => '123e4567-e89b-12d3-a456-426614174000',
    ]);
    
    // Mock the relationship
    $activity->setRelation('user', $user);
    
    expect($activity->user)->toBe($user);
    expect($activity->user->name)->toBe('John Doe');
});

it('can access subject through relationship', function () {
    $album = Mockery::mock(Album::class);
    $album->shouldReceive('getAttribute')
        ->with('id')
        ->andReturn('456e7890-e89b-12d3-a456-426614174001');
    $album->shouldReceive('getAttribute')
        ->with('title')
        ->andReturn('Test Album');
    
    $activity = new Activity([
        'subject_type' => Album::class,
        'subject_id' => '456e7890-e89b-12d3-a456-426614174001',
    ]);
    
    // Mock the relationship
    $activity->setRelation('subject', $album);
    
    expect($activity->subject)->toBe($album);
    expect($activity->subject->title)->toBe('Test Album');
});

it('can be serialized to array', function () {
    $activity = new Activity([
        'type' => 'create',
        'description' => 'Created album',
        'user_id' => '123e4567-e89b-12d3-a456-426614174000',
        'subject_type' => Album::class,
        'subject_id' => '456e7890-e89b-12d3-a456-426614174001',
        'properties' => ['key' => 'value'],
    ]);
    
    $array = $activity->toArray();
    
    expect($array)->toHaveKey('type');
    expect($array)->toHaveKey('description');
    expect($array)->toHaveKey('user_id');
    expect($array)->toHaveKey('subject_type');
    expect($array)->toHaveKey('subject_id');
    expect($array)->toHaveKey('properties');
    expect($array['type'])->toBe('create');
    expect($array['properties'])->toBe(['key' => 'value']);
});

it('can be converted to json', function () {
    $activity = new Activity([
        'type' => 'update',
        'description' => 'Updated album',
        'user_id' => '123e4567-e89b-12d3-a456-426614174000',
        'subject_type' => Album::class,
        'subject_id' => '456e7890-e89b-12d3-a456-426614174001',
    ]);
    
    $json = $activity->toJson();
    
    expect($json)->toBeString();
    expect(json_decode($json, true))->toHaveKey('type');
    expect(json_decode($json, true))->toHaveKey('description');
    expect(json_decode($json, true)['type'])->toBe('update');
});
