const path = require('path');

module.exports = {
    entry: [
        path.resolve(process.cwd(), 'src/theme/assets/promise-polyfill.js'),
        path.resolve(process.cwd(), 'src/theme/assets/main-critical.js'),
        path.resolve(process.cwd(), 'src/theme/assets/main.js')
    ],
    output: {
        publicPath: 'http://localhost:8080/_assets/',
        filename: '[name].js',
        chunkFilename: '[id].js'
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
                use: [
                    'style-loader',
                    'css-loader?importLoaders=1&minimize=true',
                    'postcss-loader'
                ]
            },
            {
                test: /\.(gif|png|jpe?g|svg)(\?.+)?$/,
                use: ['url-loader']
            },
            {
                test: /\.(eot|ttf|woff|woff2)(\?.+)?$/,
                use: ['url-loader']
            }
        ]
    },
    devtool: 'eval',
    devServer: {
        publicPath: 'http://localhost:8080/_assets/',
        headers: {
            'Access-Control-Allow-Origin': '*'
        }
    }
};
