<?php

declare(strict_types=1);

use App\Data\ImportEntryData;
use App\Data\ImportEntryDataMapper;
use App\Enums\EntryStatus;

it('maps a single object into a one-item list of ImportEntryData', function () {
    $mapper = new ImportEntryDataMapper;

    $result = $mapper->map([
        'title' => 'One',
        'content' => ['statement' => 'I am one'],
    ], [
        ['name' => 'statement', 'type' => 'textarea', 'required' => true],
    ]);

    expect($result)->toHaveCount(1)
        ->and($result[0])->toBeInstanceOf(ImportEntryData::class)
        ->and($result[0]->title)->toBe('One')
        ->and($result[0]->content)->toBe(['statement' => 'I am one'])
        ->and($result[0]->status)->toBe(EntryStatus::Published);
});

it('maps an array of objects', function () {
    $mapper = new ImportEntryDataMapper;

    $result = $mapper->map([
        ['title' => 'A', 'content' => ['url' => 'https://a.test']],
        ['title' => 'B', 'content' => ['url' => 'https://b.test'], 'status' => 'draft'],
    ], [
        ['name' => 'url', 'type' => 'text', 'required' => false],
    ]);

    expect($result)->toHaveCount(2)
        ->and($result[1])->toBeInstanceOf(ImportEntryData::class)
        ->and($result[1]->status)->toBe(EntryStatus::Draft);
});

it('maps flat field keys into a content object', function () {
    $mapper = new ImportEntryDataMapper;

    $result = $mapper->map([
        'title' => 'Flat',
        'url' => 'https://flat.test',
        'body' => 'Hello',
    ], [
        ['name' => 'url', 'type' => 'text'],
        ['name' => 'body', 'type' => 'textarea'],
    ]);

    expect($result[0]->content)->toBe([
        'url' => 'https://flat.test',
        'body' => 'Hello',
    ]);
});

it('maps legacy string content into statement', function () {
    $mapper = new ImportEntryDataMapper;

    $result = $mapper->map([
        'title' => 'Legacy',
        'content' => 'I am legacy',
    ], []);

    expect($result[0]->content)->toBe(['statement' => 'I am legacy']);
});

it('rejects a list item that is not an object', function () {
    $mapper = new ImportEntryDataMapper;

    $mapper->map(['not-an-object'], []);
})->throws(InvalidArgumentException::class, 'must be a JSON object');

it('rejects entries without a title', function () {
    $mapper = new ImportEntryDataMapper;

    $mapper->map([['content' => ['statement' => 'nope']]], []);
})->throws(InvalidArgumentException::class, 'title');

it('treats empty content object as a valid object', function () {
    $mapper = new ImportEntryDataMapper;

    $result = $mapper->map([
        'title' => 'Empty content',
        'content' => [],
    ], [
        ['name' => 'body', 'type' => 'textarea', 'required' => false],
    ]);

    expect($result[0]->content)->toBe([]);
});
