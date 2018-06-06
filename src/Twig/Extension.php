<?php

namespace React\Website\Twig;

use LitEmoji\LitEmoji;

class Extension extends \Twig_Extension
{
    public function getFunctions(): array
    {
        return [
            new \Twig_Function(
                'embed_asset',
                [$this, 'embedAsset']
            ),
            new \Twig_Function(
                'participation_svg',
                [$this, 'participationSvg']
            )
        ];
    }

    public function getFilters(): array
    {
        return [
            new \Twig_Filter(
                'display_url',
                [$this, 'displayUrl']
            ),
            new \Twig_Filter(
                'strip_title',
                [$this, 'stripTitle']
            ),
            new \Twig_Filter(
                'emoji',
                [$this, 'emoji']
            )
        ];
    }

    public function embedAsset(string $path, string $targetUrl = null): string
    {
        $content = file_get_contents($path);

        if (null !== $targetUrl) {
            $rewriteUrl = function ($matches) use ($targetUrl) {
                $url = $matches['url'];

                // First check also matches protocol-relative urls like //example.com
                if ((isset($url[0]) && '/' === $url[0]) || false !== strpos($url, '://') || 0 === strpos($url, 'data:')) {
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

    public function participationSvg(string $repo, array $participation): string
    {
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

    public function displayUrl(string $url): string
    {
        $host = parse_url($url, PHP_URL_HOST);

        if (!$host) {
            return $url;
        }

        return str_ireplace('www.', '', $host);
    }

    public function stripTitle(string $string): string
    {
        return preg_replace('/^<h1[^>]*?>.*?<\/h1>/si', '', trim($string));
    }

    public function emoji(string $string): string
    {
        return LitEmoji::encodeUnicode($string);
    }
}
