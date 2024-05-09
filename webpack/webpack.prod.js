const merge = require('webpack-merge');
const common = require('./webpack.common.js');

module.exports = merge(common, {
    mode: 'production',
    performance: {
        hints: false,
        maxEntrypointSize: 1024,
        maxAssetSize: 1024
    }
});