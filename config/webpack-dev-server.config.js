const path = require('path');

module.exports = {
    mode: 'development',
    entry: [
        path.resolve(process.cwd(), 'theme/assets/index.js')
    ],
    output: {
        publicPath: 'http://localhost:8080/_assets/',
        filename: '[name].js',
        chunkFilename: '[id].js'
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
                    'style-loader',
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
    devtool: 'eval',
    devServer: {
        publicPath: 'http://localhost:8080/_assets/',
        headers: {
            'Access-Control-Allow-Origin': '*'
        }
    }
};
