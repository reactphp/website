const path = require('path');
const webpack = require('webpack');
const ExtractTextPlugin = require("extract-text-webpack-plugin");
const ManifestPlugin = require('webpack-manifest-plugin');

module.exports = {
    entry: {
        'main-critical': [
            path.resolve(process.cwd(), 'theme/assets/webpack-public-path.js'),
            path.resolve(process.cwd(), 'theme/assets/main-critical.js')
        ],
        main: [
            path.resolve(process.cwd(), 'theme/assets/webpack-public-path.js'),
            path.resolve(process.cwd(), 'theme/assets/promise-polyfill.js'),
            path.resolve(process.cwd(), 'theme/assets/main.js')
        ]
    },
    output: {
        path: path.resolve(process.cwd(), 'static-files/assets'),
        filename: '[name].[chunkhash].js',
        chunkFilename: '[name].[chunkhash].js'
    },
    module: {
        rules: [
            {
                test: /\.js$/,
                exclude: /node_modules/,
                use: ['babel-loader']
            },
            {
                test: /\.css$/,
                use: ExtractTextPlugin.extract({
                    fallback: 'style-loader',
                    use: [
                        'css-loader?importLoaders=1&minimize=true',
                        'postcss-loader'
                    ]
                })
            },
            {
                test: /\.(gif|png|jpe?g|svg)(\?.+)?$/,
                use: ['file-loader']
            },
            {
                test: /\.(eot|ttf|woff|woff2)(\?.+)?$/,
                use: ['file-loader']
            }
        ]
    },
    plugins: [
        new ExtractTextPlugin('[name].[chunkhash].css'),
        new webpack.optimize.UglifyJsPlugin({
            output: {
                comments: false
            }
        }),
        new ManifestPlugin()
    ]
};
