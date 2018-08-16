<?php

namespace React\Website\Data;

use Github\Client;
use Github\ResultPager;
use function igorw\retry;
use Psr\Cache\CacheItemPoolInterface;

function components(Client $client, CacheItemPoolInterface $markdownCache): array
{
    return array_map(function ($component) use ($client, $markdownCache) {
        [$username, $repository] = explode('/', $component['repository']);

        $component['contributors'] = $client->repo()->contributors($username, $repository);

        $component['participation'] = retry(3, function () use ($client, $username, $repository){
            $participation = $client->repo()->participation($username, $repository);

            if (!isset($participation['all'])) {
                throw new \RuntimeException(
                    "Could not fetch participation for $username/$repository from the Github API"
                );
            }

            return $participation;
        });

        $apiReleases = (new ResultPager($client))
            ->fetchAll(
                $client->repo()->releases(),
                'all',
                [$username, $repository, ['per_page' => 100]]
            );

        $releases = [];

        foreach ($apiReleases as $release) {
            $cacheKey = 'gfm' . md5($component['repository'] . $release['body']);

            $cacheItem = $markdownCache->getItem($cacheKey);

            if ($cacheItem->isHit()) {
                $html = $cacheItem->get();
            } else {
                $html = $client->markdown()->render(
                    $release['body'],
                    'gfm',
                    $component['repository']
                );

                $cacheItem->set($html);
                $markdownCache->save($cacheItem);
            }

            $date = new \DateTimeImmutable($release['created_at']);

            $releases[(int) $date->format('U')] =  [
                'version' => ltrim($release['tag_name'], 'v'),
                'tag' => $release['tag_name'],
                'date' => $date,
                'html' => $html,
                'url' => $release['html_url'],
                'component' => $component['title'],
                'repository' => $component['repository'],
                'author' => [
                    'name' => $release['author']['login'],
                    'uri' => $release['author']['html_url'],
                    'avatar' => $release['author']['avatar_url'],
                ]
            ];
        }

        krsort($releases, SORT_NATURAL);

        $component['releases'] = array_values($releases);

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

function releases(array $components): array
{
    $releases = [];

    foreach ($components as $component) {
        foreach ($component['releases'] as $release) {
            $time = (int) $release['date']->format('U');

            $releases[$time . '-' . $component['repository']] = $release;
        }
    }

    krsort($releases, SORT_NATURAL);

    return array_values($releases);
}

function releases_by_year(array $releases): array
{
    $byYear = [];

    foreach ($releases as $release) {
        $year = (int) $release['date']->format('Y');

        if (!isset($byYear[$year])) {
            $byYear[$year] = [];
        }

        $byYear[$year][] = $release;
    }

    return $byYear;
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
