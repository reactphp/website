<?php

try {
    (new Dotenv\Dotenv(getcwd()))->load();
} catch (Dotenv\Exception\InvalidPathException $e) {
    // Ignore missing .env
}

return function (Pimple\Container $container) {
    $data = include __DIR__ . '/../src/data.php';

    $container['react.components'] = function ($container) use ($data) {
        /** @var Github\Client $client */
        $client = $container['github.client'];

        return array_map(function ($component) use ($client) {
            [$username, $repository] = explode('/', $component['repository']);

            $tags = $client->repo()->tags($username, $repository);

            $versions = array_filter(
                array_map(function ($tag) {
                    return $tag['name'];
                }, $tags),
                function ($version) use ($component) {
                    if (!isset($component['exclude_versions'])) {
                        return true;
                    }

                    return !in_array($version, (array) $component['exclude_versions'], true);
                }
            );

            $component['versions'] = array_values($versions);

            $response = $client->getHttpClient()->get(
                'repos/' . $component['repository'],
                [
                    'Accept' => 'application/vnd.github.drax-preview+json'
                ]
            );

            return array_merge(
                Github\HttpClient\Message\ResponseMediator::getContent($response),
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

    $container['github.url_generator'] = $container->extend('github.url_generator', function (callable $urlGenerator) {
        return function (string $repository, string $url, string $cwd = null) use ($urlGenerator) {
            $process = new Symfony\Component\Process\Process('git describe --tags', $cwd);
            $process->run();

            // Might return HEAD if not in a tag checkout
            $version = trim($process->getOutput());

            if ($version && 'HEAD' !== $version) {
                return 'https://github.com/' . $repository . '/blob/' . $version . '/' . ltrim($url, '/');
            }

            return $urlGenerator($repository, $url, $cwd);
        };
    });

    $container['template.theme'] = __DIR__ . '/../src/theme';

    $container['template.map'] = [
        'index.html' => 'homepage.html.twig'
    ];

    $container['template.renderer'] = $container->extend('template.renderer', function (callable $renderer, $container) {
        return function (string $name, array $context = []) use ($renderer, $container) {
            /** @var \Symfony\Component\Finder\SplFileInfo $documentInput */
            $documentInput = $context['berti']['document']->input;

            $path = dirname($documentInput->getRealPath());

            $repo = $container['github.repository_detector']($path);

            if (!$repo) {
                return $renderer($name, $context);
            }

            $components = array_filter($container['react.components'], function ($component) use ($repo) {
                return $component['full_name'] === $repo;
            });

            $component = reset($components);

            if (!$component) {
                return $renderer($name, $context);
            }

            if ('master' === basename(dirname($documentInput->getRealPath()))) {
                $version = 'master';
            } else {
                $process = new Symfony\Component\Process\Process('git describe --tags', $path);
                $process->run();

                // Might return HEAD if not in a tag checkout
                $version = trim($process->getOutput());
            }

            $context['component'] = $component;
            $context['component_version'] = $version;
            $name = 'component.html.twig';

            if ('LICENSE' === $documentInput->getFilename()) {
                $name = 'component-license.html.twig';
            }

            return $renderer($name, $context);
        };
    });

    $container['github.markdown.filter'] = $container->extend('github.markdown.filter', function (callable $filter, $container) {
        // Replaces internal links from https://github.com/reactphp/{component}
        // to https://reactphp.org/{component}
        return function (
            string $repository,
            string $html,
            Berti\Document $document,
            array $documentCollection,
            array $assetCollection
        ) use ($filter, $container) {
            $html = $filter(
                $repository,
                $html,
                $document,
                $documentCollection,
                $assetCollection
            );

            $map = [];

            foreach ($documentCollection as $doc) {
                $inputPath = $doc->input->getRelativePathname();
                $outputPath = $doc->output->getRelativePathname();

                if (basename($inputPath) === $container['input.directory_index']) {
                    $inputPath = dirname($inputPath);
                }

                if (basename($outputPath) === $container['output.directory_index']) {
                    $outputPath = rtrim(dirname($outputPath), '/') . '/';
                }

                $map[$inputPath] = Berti\uri_rewriter(
                    $outputPath,
                    '/',
                    $document->output->getRelativePathname()
                );
            }

            $repos = array_map(function ($component) {
                return $component['name'];
            }, $container['react.components']);

            $callback = function ($matches) use ($map) {
                $url = $matches[2] . ($matches[3] ?? '');

                $hash = '';

                if (false !== strpos($url, '#')) {
                    [$url, $hash] = explode('#', $url);
                }

                if (!isset($map[$url])) {
                    return $matches[0];
                }

                if ('' !== $hash) {
                    $hash = '#' . $hash;
                }

                return 'href="' . $map[$url] . $hash . '"';
            };

            $html = preg_replace_callback(
                '/href=(["\']?)https:\/\/github.com\/reactphp\/+([' . preg_quote(implode('|', $repos), '/') . ']+)([^"\']+)?\\1/i',
                $callback,
                $html
            );

            return $html;
        };
    });

    $container['react.repository_contributors'] = function ($container) {
        /** @var Github\Client $client */
        $client = $container['github.client'];

        return function ($repo) use ($client) {
            [$username, $repository] = explode('/', $repo);

            return $client->repo()->contributors($username, $repository);
        };
    };

    $container['react.repository_participation'] = function ($container) {
        /** @var Github\Client $client */
        $client = $container['github.client'];

        return function ($repo) use ($client) {
            [$username, $repository] = explode('/', $repo);

            return $client->repo()->participation($username, $repository);
        };
    };

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

        $twig->addFunction(
            new \Twig_Function(
                'contributors',
                function ($repo) use ($container) {
                    return $container['react.repository_contributors']($repo);
                }
            )
        );

        $twig->addFunction(
            new \Twig_Function(
                'participation_svg',
                function ($repo) use ($container) {
                    $participation =  $container['react.repository_participation']($repo);

                    $width = 320;
                    $height = 40;

                    $prefix = str_replace('/', '-', $repo);

                    $x = 0;
                    $offset = floor($width / count($participation['all']));

                    $points = array_map(function ($value) use (&$x, $offset) {
                        $currX = $x;
                        $x += $offset;

                        return $currX . ',' . ($value + 1);
                    }, $participation['all']);

                    $pointString = implode(' ', $points);
                    $rectHeight = $height + 2;

                    return <<<EOF
<svg width="$width" height="$rectHeight">
    <defs>
        <linearGradient id="$prefix-participation-gradient" x1="0" x2="0" y1="1" y2="0">
            <stop offset="10%" stop-color="#40a977"></stop>
            <stop offset="90%" stop-color="#ba3525"></stop>
        </linearGradient>
        <mask id="$prefix-participation-sparkline" x="0" y="0" width="$width" height="$height" >
            <polyline 
                transform="translate(0, $height) scale(1,-1)"
                points="$pointString" 
                fill="transparent" 
                stroke="#40a977" 
                stroke-width="2"
            >
        </mask>
    </defs>

    <g transform="translate(0, -1.5)">
        <rect 
            x="0" 
            y="-2" 
            width="$width" 
            height="$rectHeight"
            style="stroke:none;fill:url(#$prefix-participation-gradient);mask:url(#$prefix-participation-sparkline)"
        ></rect>
    </g>
</svg>
EOF;
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
                'strip_title',
                function ($string) {
                    return preg_replace('/^<h1[^>]*?>.*?<\/h1>/si', '', trim($string));
                }
            )
        );

        $twig->addFilter(
            new \Twig_Filter(
                'emoji',
                function ($string) {
                    return \LitEmoji\LitEmoji::encodeUnicode($string);
                }
            )
        );

        $twig->addGlobal('container', $container);

        $twig->addGlobal(
            'asset_manifest',
            json_decode(
                file_get_contents(__DIR__.'/../src/static-files/assets/manifest.json'),
                true
            )
        );

        $twig->addGlobal(
            'use_asset_dev_server',
            'true' === getenv('USE_ASSET_DEV_SERVER')
        );

        $twig->addGlobal('base_url', rtrim(getenv('DEPLOY_URL'), '/'));

        return $twig;
    });

    $container['asset.finder'] = $container->protect(function ($path) {
        $finder = new Symfony\Component\Finder\Finder();

        return $finder
            ->files()
            ->ignoreDotFiles(false)
            ->notName('.DS_Store')
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
