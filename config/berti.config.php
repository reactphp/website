<?php

return function (Pimple\Container $container) {
    $data = include __DIR__ . '/../src/data.php';

    $container['react.components'] = function ($container) use ($data) {
        /** @var Github\Client $client */
        $client = $container['github.client'];

        return array_map(function($component) use ($client) {
            list($username, $repository) = explode('/', $component['repository']);

            return array_merge(
                $client->repo()->show($username, $repository),
                ['tags' => $client->repo()->tags($username, $repository)],
                $component
            );
        }, $data['components']);
    };

    $container['react.components_by_category'] = function ($container) {
        $components = $container['react.components'];
        $byCategory = [];

        foreach ($components as $component) {
            if (!isset($byCategory[$component['category']])) {
                $byCategory[$component['category']] = [];
            }

            $byCategory[$component['category']][] = $component;
        }

        return $byCategory;
    };

    $container['react.built_with'] = function ($container) use ($data) {
        /** @var Github\Client $client */
        $client = $container['github.client'];

        return array_map(function($component) use ($client) {
            list($username, $repository) = explode('/', $component['repository']);

            return array_merge(
                $client->repo()->show($username, $repository),
                $component
            );
        }, $data['built_with']);
    };

    $container['react.articles'] = $data['articles'];
    $container['react.talks'] = $data['talks'];

    $container['github.url_generator'] = $container->extend('github.url_generator', function (callable $urlGenerator, $container){
        return function (string $repository, string $url, string $cwd = null) use ($urlGenerator, $container) {
            $components = array_filter($container['react.components'], function ($component) use ($repository) {
                return $component['full_name'] === $repository;
            });

            $component = reset($components);

            if (isset($component['tags'][0]['name'])) {
                return 'https://github.com/' . $repository . '/blob/' . $component['tags'][0]['name'] . '/' . ltrim($url, '/');
            }

            return $urlGenerator($repository, $url, $cwd);
        };
    });

    $container['template.theme'] = __DIR__ . '/../src/theme';

    $container['template.map'] = [
        'index.html' => 'homepage.html.twig'
    ];

    $container['twig'] = $container->extend('twig', function (\Twig_Environment $twig, $container) {
        $twig->addFunction(
            new \Twig_Function(
                'embed',
                function ($path, $targetUrl = null) {
                    $content = file_get_contents($path);

                    if (null !== $targetUrl) {
                        $rewriteUrl = function ($matches) use ($targetUrl) {
                            $url = $matches['url'];

                            // First check also matches protocol-relative urls like //example.com
                            if ((isset($url[0])  && '/' === $url[0]) || false !== strpos($url, '://') || 0 === strpos($url, 'data:')) {
                                return $matches[0];
                            }

                            return str_replace($url, trim($targetUrl, '/') . '/' . $url, $matches[0]);
                        };

                        $content = preg_replace_callback('/url\((["\']?)(?<url>.*?)(\\1)\)/', $rewriteUrl, $content);
                        $content = preg_replace_callback('/@import (?!url\()(\'|"|)(?<url>[^\'"\)\n\r]*)\1;?/', $rewriteUrl, $content);
                        // Handle 'src' values (used in e.g. calls to AlphaImageLoader, which is a proprietary IE filter)
                        $content = preg_replace_callback('/\bsrc\s*=\s*(["\']?)(?<url>.*?)(\\1)/i', $rewriteUrl, $content);
                    }

                    return $content;
                }
            )
        );

        $twig->addFilter(
            new \Twig_Filter(
                'display_url',
                function ($url) {
                    $host = parse_url($url, PHP_URL_HOST);

                    if (!$host) {
                        return $url;
                    }

                    return str_ireplace('www.', '', $host);
                }
            )
        );

        $twig->addFilter(
            new \Twig_Filter(
                'emoji',
                function ($string) use ($container) {
                    return \LitEmoji\LitEmoji::encodeUnicode($string);
                }
            )
        );

        $twig->addGlobal('container', $container);

        $twig->addGlobal(
            'use_asset_dev_server',
            'true' === getenv('BERTI_USE_ASSET_DEV_SERVER')
        );

        $twig->addGlobal('base_url', rtrim(getenv('DEPLOY_URL'), '/'));

        return $twig;
    });

    $container['asset.finder'] = $container->protect(function ($path) {
        $finder = new Symfony\Component\Finder\Finder();

        return $finder
            ->files()
            ->ignoreDotFiles(false)
            ->in($path . '/src/static-files');
    });

    $container['document.finder'] = $container->protect(function ($path) {
        $finder = new Symfony\Component\Finder\Finder();

        return $finder
            ->name('/\.md$/')
            ->name('LICENSE')
            ->files()
            ->in($path . '/tmp/components')
            ->in($path . '/src/pages');
    });
};
