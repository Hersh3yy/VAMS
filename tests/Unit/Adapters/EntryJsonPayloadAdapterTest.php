<?php

declare(strict_types=1);

use App\Adapters\EntryJsonPayloadAdapter;

it('adapts a single object into a one-item list', function () {
    $adapter = new EntryJsonPayloadAdapter;

    $result = $adapter->adapt([
        'title' => 'One',
        'content' => ['statement' => 'I am one'],
    ], [
        ['name' => 'statement', 'type' => 'textarea', 'required' => true],
    ]);

    expect($result)->toHaveCount(1)
        ->and($result[0]['title'])->toBe('One')
        ->and($result[0]['content'])->toBe(['statement' => 'I am one'])
        ->and($result[0]['status'])->toBe('published');
});

it('adapts an array of objects', function () {
    $adapter = new EntryJsonPayloadAdapter;

    $result = $adapter->adapt([
        ['title' => 'A', 'content' => ['url' => 'https://a.test']],
        ['title' => 'B', 'content' => ['url' => 'https://b.test'], 'status' => 'draft'],
    ], [
        ['name' => 'url', 'type' => 'text', 'required' => false],
    ]);

    expect($result)->toHaveCount(2)
        ->and($result[1]['status'])->toBe('draft');
});

it('adapts flat field keys into a content object', function () {
    $adapter = new EntryJsonPayloadAdapter;

    $result = $adapter->adapt([
        'title' => 'Flat',
        'url' => 'https://flat.test',
        'body' => 'Hello',
    ], [
        ['name' => 'url', 'type' => 'text'],
        ['name' => 'body', 'type' => 'textarea'],
    ]);

    expect($result[0]['content'])->toBe([
        'url' => 'https://flat.test',
        'body' => 'Hello',
    ]);
});

it('adapts legacy string content into statement', function () {
    $adapter = new EntryJsonPayloadAdapter;

    $result = $adapter->adapt([
        'title' => 'Legacy',
        'content' => 'I am legacy',
    ], []);

    expect($result[0]['content'])->toBe(['statement' => 'I am legacy']);
});

it('rejects a list item that is not an object', function () {
    $adapter = new EntryJsonPayloadAdapter;

    $adapter->adapt(['not-an-object'], []);
})->throws(\InvalidArgumentException::class, 'must be a JSON object');

it('rejects entries without a title', function () {
    $adapter = new EntryJsonPayloadAdapter;

    $adapter->adapt([['content' => ['statement' => 'nope']]], []);
})->throws(\InvalidArgumentException::class, 'title');
