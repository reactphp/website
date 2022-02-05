const path = require('path');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const { CleanWebpackPlugin } = require('clean-webpack-plugin');
const PostCSSAssetsPlugin = require('postcss-assets-webpack-plugin');
const { WebpackManifestPlugin } = require('webpack-manifest-plugin');

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
            publicPath: '', // see https://github.com/shellscape/webpack-manifest-plugin/issues/229#issuecomment-737617994
            filename: '[name].[contenthash:8].js',
            chunkFilename: '[name].[contenthash:8].js',
            assetModuleFilename: '[name].[contenthash:8][ext][query]'
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
                                postcssOptions: {
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
                        }
                    ],
                },
                {
                    test: /\.(gif|png|jpe?g|svg)$/i,
                    type: 'asset/resource',
                },
                {
                    test: /\.(woff|woff2|eot|ttf|otf)$/,
                    type: 'asset/resource',
                },
            ],
        },
        plugins: [
            new CleanWebpackPlugin(),
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
            new WebpackManifestPlugin({}),
        ]
    };
};
