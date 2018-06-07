<?php

try {
    (new Dotenv\Dotenv(getcwd()))->load();
} catch (Dotenv\Exception\InvalidPathException $e) {
    // Ignore missing .env
}

return function (Pimple\Container $container) {
    $container['data.components'] = function (Pimple\Container $container) {
        return React\Website\Data\components($container['github.client']);
    };

    $container['data.components_by_category'] = function (Pimple\Container $container) {
        return React\Website\Data\components_by_category($container['data.components']);
    };

    $container['data.built_with'] = function (Pimple\Container $container) {
        return React\Website\Data\built_with($container['github.client']);
    };

    $container['data.articles'] = function () {
        return React\Website\Data\articles();
    };

    $container['data.talks'] = function () {
        return React\Website\Data\talks();
    };

    $container['template.theme'] = __DIR__ . '/../theme';

    $container['template.map'] = [
        'index.html' => 'homepage.html.twig'
    ];

    $container['github.url_generator'] = $container->extend('github.url_generator', function (callable $urlGenerator) {
        return function (string $repository, string $url, string $cwd = null) use ($urlGenerator) {
            return React\Website\Berti\github_url_generator(
                $urlGenerator,
                $repository,
                $url,
                $cwd
            );
        };
    });

    $container['github.markdown.filter'] = $container->extend('github.markdown.filter', function (callable $filter, Pimple\Container $container) {
        return function (
            string $repository,
            string $html,
            Berti\Document $document,
            array $documentCollection,
            array $assetCollection
        ) use ($filter, $container) {
            return React\Website\Berti\github_markdown_filter(
                $container['data.components'],
                $container['input.directory_index'],
                $container['output.directory_index'],
                $filter,
                $repository,
                $html,
                $document,
                $documentCollection,
                $assetCollection
            );
        };
    });

    $container['template.renderer'] = $container->extend('template.renderer', function (callable $renderer, Pimple\Container $container) {
        return function (string $name, array $context = []) use ($renderer, $container) {
            return React\Website\Berti\template_renderer(
                $container['data.components'],
                $container['github.repository_detector'],
                $renderer,
                $name,
                $context
            );
        };
    });

    $container['twig'] = $container->extend('twig', function (\Twig_Environment $twig, Pimple\Container $container) {
        $twig->addExtension(new React\Website\Twig\Extension());

        $twig->addGlobal(
            'components',
            $container['data.components']
        );

        $twig->addGlobal(
            'components_by_category',
            $container['data.components_by_category']
        );

        $twig->addGlobal(
            'built_with',
            $container['data.built_with']
        );

        $twig->addGlobal(
            'articles',
            $container['data.articles']
        );

        $twig->addGlobal(
            'talks',
            $container['data.talks']
        );

        $twig->addGlobal(
            'asset_manifest',
            json_decode(
                file_get_contents(__DIR__ . '/../static-files/assets/manifest.json'),
                true
            )
        );

        $twig->addGlobal(
            'use_asset_dev_server',
            'true' === getenv('USE_ASSET_DEV_SERVER')
        );

        $twig->addGlobal(
            'base_url',
            rtrim(getenv('DEPLOY_URL'), '/')
        );

        return $twig;
    });

    $container['asset.finder'] = $container->protect(function ($path) {
        return (new Symfony\Component\Finder\Finder())
            ->files()
            ->ignoreDotFiles(false)
            ->notName('.DS_Store')
            ->in($path . '/static-files');
    });

    $container['document.finder'] = $container->protect(function ($path) {
        return (new Symfony\Component\Finder\Finder())
            ->name('/\.md$/')
            ->name('LICENSE')
            ->files()
            ->in($path . '/tmp/components')
            ->in($path . '/pages');
    });
};
