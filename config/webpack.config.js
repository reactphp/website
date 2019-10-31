const path = require('path');
const webpack = require('webpack');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const { CleanWebpackPlugin } = require('clean-webpack-plugin');
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
            filename: '[name].[contenthash:8].js',
            chunkFilename: '[name].[contenthash:8].js',
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
                                            corejs: 3,
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
                            }
                        },
                        {
                            loader: 'postcss-loader',
                            options: {
                                plugins: [
                                    require('postcss-import')(),
                                    require('postcss-flexbugs-fixes')(),
                                    require('postcss-preset-env')({
                                        stage: 0,
                                        autoprefixer: {
                                            flexbox: 'no-2009',
                                            grid: true,
                                        }
                                    }),
                                ]
                            }
                        }
                    ],
                },
                {
                    test: /\.(gif|png|jpe?g|svg)$/i,
                    use: [
                        {
                            loader: 'file-loader',
                            options: {
                                name: '[name].[hash:8].[ext]',
                            }
                        },
                    ],
                },
                {
                    test: /\.(woff|woff2|eot|ttf|otf)$/,
                    use: [
                        {
                            loader: 'file-loader',
                            options: {
                                name: '[name].[hash:8].[ext]',
                            }
                        },
                    ],
                },
            ],
        },
        plugins: [
            new CleanWebpackPlugin(),
            // https://webpack.js.org/guides/caching/#module-identifiers
            new webpack.HashedModuleIdsPlugin(),
            new MiniCssExtractPlugin({
                filename: '[name].[contenthash:8].css',
                chunkFilename: '[name].[contenthash:8].css',
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
