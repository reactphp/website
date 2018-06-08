<?php

namespace React\Website\Berti;

use Berti\Document;
use function React\Website\Data\releases_by_year;
use Symfony\Component\Process\Process;
use function Berti\uri_rewriter;

/**
 * The default `Berti\github_url_generator()` function replaces relative links
 * with absolute canonical links in markdown documents.
 *
 * For example, if there's a markdown link like `[Examples](examples/)`, the
 * relative URL is replaced with something like
 * https://github.com/reactphp/event-loop/tree/707a875d003156b4e0b71d75604175058a839a6e/examples
 *
 * This function extends the generator and tries to use a URL pointing to the
 * tag tree, eg. https://github.com/reactphp/event-loop/tree/v0.5.2/examples.
 *
 * We can always use the ../blob/.. part because GitHub redirects from
 * ../blob/.. to ../tree/.. for directories.
 */
function github_url_generator(
    callable $bertiUrlGenerator,
    string $repository,
    string $url,
    string $cwd = null
): string
{
    $process = new Process('git describe --tags', $cwd);
    $process->run();

    // Might return HEAD if not in a tag checkout
    $version = trim($process->getOutput());

    if ($version && 'HEAD' !== $version) {
        // Always use ../blob/.. because GitHub redirects to ../tree/.. for directories
        return 'https://github.com/' . $repository . '/blob/' . $version . '/' . ltrim($url, '/');
    }

    return $bertiUrlGenerator($repository, $url, $cwd);
}

/**
 * This function extends the `Berti\twig_renderer()` to dynamically switch
 * templates, eg. if a component repository is detected.
 *
 * It also passes additional component context data when rendering the template.
 */
function template_renderer(
    array $components,
    callable $repositoryDetector,
    callable $bertiRenderer,
    string $name,
    array $context = []
): string
{
    /** @var \Symfony\Component\Finder\SplFileInfo $documentInput */
    $documentInput = $context['berti']['document']->input;

    if ('changelog.md' === $documentInput->getRelativePathname()) {
        return $bertiRenderer('changelog.html.twig', $context);
    }

    $repo = $repositoryDetector(dirname($documentInput->getRealPath()));

    if (!$repo) {
        return $bertiRenderer($name, $context);
    }

    $components = array_filter(
        $components,
        function ($component) use ($repo) {
            return $component['full_name'] === $repo;
        }
    );

    $component = reset($components);

    if (!$component) {
        return $bertiRenderer($name, $context);
    }

    $context['component'] = $component;

    $context['component_releases_by_year'] = releases_by_year($component['releases']);

    switch ($documentInput->getFilename()) {
        case 'LICENSE':
            $name = 'component-license.html.twig';
            break;
        case 'CHANGELOG.md':
            $name = 'component-changelog.html.twig';
            break;
        default:
            $name = 'component.html.twig';
            break;
    }

    return $bertiRenderer($name, $context);
}

/**
 * Additional markdown filter which replaces links to other components with
 * internal relative links.
 *
 * For example, if there's a markdown link like
 * `[Event Loop](https://github.com/reactphp/event-loop)`, the URL is replaced
 * with `../event-loop/`.
 */
function github_markdown_filter(
    array $components,
    string $inputDirectoryIndex,
    string $outputDirectoryIndex,
    callable $bertiFilter,
    string $repository,
    string $html,
    Document $document,
    array $documentCollection,
    array $assetCollection
): string
{
    $html = $bertiFilter(
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

        if (basename($inputPath) === $inputDirectoryIndex) {
            $inputPath = dirname($inputPath);
        }

        if (basename($outputPath) === $outputDirectoryIndex) {
            $outputPath = rtrim(dirname($outputPath), '/') . '/';
        }

        $map[$inputPath] = uri_rewriter(
            $outputPath,
            '/',
            $document->output->getRelativePathname()
        );
    }

    $repos = array_map(
        function ($component) {
            return $component['name'];
        },
        $components
    );

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
}
