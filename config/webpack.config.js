const path = require('path');
const webpack = require('webpack');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const CleanWebpackPlugin = require('clean-webpack-plugin');
const PostCSSAssetsPlugin = require('postcss-assets-webpack-plugin');
const ManifestPlugin = require('webpack-manifest-plugin');

module.exports = () => {
    const mode = 'production';
    const targetPath = path.resolve(process.cwd(), 'static-files/assets');

    return {
        mode: mode,
        entry: {
            'main': [
                path.resolve(process.cwd(), 'theme/assets/webpack-public-path.js'),
                path.resolve(process.cwd(), 'theme/assets/index.js'),
            ],
        },
        output: {
            path: path.resolve(process.cwd(), targetPath),
            filename: '[name].[contenthash].js',
            chunkFilename: '[name].[contenthash].js',
        },
        optimization: {
            runtimeChunk: 'single'
        },
        resolve: {
            extensions: ['.js', '.css', '.json']
        },
        module: {
            strictExportPresence: true,
            rules: [
                {
                    test: /\.js$/,
                    use: [
                        {
                            loader: 'babel-loader',
                            options: {
                                cacheDirectory: true,
                                babelrc: false,
                                presets: [
                                    [
                                        '@babel/preset-env',
                                        {
                                            useBuiltIns: 'entry',
                                            modules: false,
                                            debug: false,
                                        }
                                    ]
                                ],
                                plugins: [
                                    '@babel/plugin-syntax-dynamic-import',
                                ]
                            },
                        },
                    ],
                },
                {
                    test: /\.css$/,
                    use: [
                        mode !== 'production' ? 'style-loader' : MiniCssExtractPlugin.loader,
                        {
                            loader: 'css-loader',
                            options: {
                                importLoaders: 1,
                                minimize: false, // Minification done by the PostCSSAssetsPlugin
                            }
                        },
                        {
                            loader: 'postcss-loader',
                            options: {
                                plugins: [
                                    require('postcss-import')(),
                                    require('postcss-cssnext')(),
                                    require('postcss-flexbugs-fixes')()
                                ]
                            }
                        }
                    ],
                },
                {
                    test: /\.(gif|png|jpe?g|svg)$/i,
                    use: [
                        'file-loader',
                    ],
                },
                {
                    test: /\.(woff|woff2|eot|ttf|otf)$/,
                    use: [
                        'file-loader',
                    ],
                },
            ],
        },
        plugins: [
            new CleanWebpackPlugin([targetPath + '/*'], {
                root: process.cwd()
            }),
            // https://webpack.js.org/guides/caching/#module-identifiers
            new webpack.HashedModuleIdsPlugin(),
            new MiniCssExtractPlugin({
                filename: '[name].[contenthash].css',
                chunkFilename: '[name].[contenthash].css',
            }),
            new PostCSSAssetsPlugin({
                plugins: [
                    require('cssnano')({
                        preset: ['default', {
                            discardComments: {
                                removeAll: true,
                            },
                        }]
                    }),
                ],
            }),
            new ManifestPlugin(),
        ]
    };
};
