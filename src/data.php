<?php

namespace React\Website\Data;

use Github\Client;

function components(Client $client): array
{
    return array_map(function ($component) use ($client) {
        [$username, $repository] = explode('/', $component['repository']);

        $component['versions'] = array_map(
            function ($tag) {
                return $tag['name'];
            },
            $client->repo()->tags($username, $repository)
        );

        $component['contributors'] = $client->repo()->contributors($username, $repository);
        $component['participation'] = $client->repo()->participation($username, $repository);

        return array_merge(
            $client->repo()->show($username, $repository),
            $component
        );
    }, include __DIR__ . '/../data/components.php');
}

function components_by_category(array $components): array
{
    $byCategory = [];

    foreach ($components as $component) {
        if (!isset($byCategory[$component['category']])) {
            $byCategory[$component['category']] = [];
        }

        $byCategory[$component['category']][] = $component;
    }

    return $byCategory;
}

function built_with(Client $client): array
{
    return array_map(function ($component) use ($client) {
        [$username, $repository] = explode('/', $component['repository']);

        return array_merge(
            $client->repo()->show($username, $repository),
            $component
        );
    }, include __DIR__ . '/../data/built_with.php');
}

function articles(): array
{
    return include __DIR__ . '/../data/articles.php';
}

function talks(): array
{
    return include __DIR__ . '/../data/talks.php';
}
