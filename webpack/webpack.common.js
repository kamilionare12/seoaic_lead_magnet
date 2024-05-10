const path = require('path');
const webpack = require('webpack');

// include the js minification plugin
const TerserPlugin = require('terser-webpack-plugin');

// include the css extraction and minification plugins
const MiniCssExtractPlugin = require("mini-css-extract-plugin");
const OptimizeCSSAssetsPlugin = require("optimize-css-assets-webpack-plugin");
const entryPoints = {
  'main': './src/js/main.js',
};

module.exports = {
  mode: "production",
  entry: entryPoints,
  output: {
    filename: '../assets/js/[name].min.js',
    path: path.resolve(__dirname)
  },
  module: {
    rules: [
      // perform js babelization on all .js files
      {
        test: /\.js$/,
        exclude: /node_modules/,
        use: {
          loader: "babel-loader",
          options: {
            presets: ['babel-preset-env']
         }
        }
      },
      // compile all .scss files to plain old css
      {
        test: /\.(sass|scss)$/,
        use: [MiniCssExtractPlugin.loader, 'css-loader', 'sass-loader']
      },
      {
        test: /\.(eot|ttf|woff|woff2)$/,
        use: {
          loader: 'file-loader',
          options: {
            name: '[name].[ext]',
            outputPath: '../assets/fonts/',
            publicPath: '../fonts/',
          },
        },
      },
      {
        test: /\.(svg)$/,
        use: {
          loader: 'file-loader',
          options: {
            name: '[name].[ext]',
            outputPath: '../assets/images/icons',
            publicPath: '../images/icons',
          },
        },
      },
      {
        test: /\.(png|jpg|gif)$/,
        use: {
          loader: 'file-loader',
          options: {
            name: '[name].[ext]',
            outputPath: '../assets/images',
            publicPath: '../images',
          },
        },
      },
    ]
  },
  plugins: [
    // extract css into dedicated file
    new MiniCssExtractPlugin({
        filename: '../assets/css/[name].min.css'
    }),
    new webpack.SourceMapDevToolPlugin({
      filename: 'src/js/[name].js.map'
    }),
    new webpack.ProvidePlugin({
      $: 'jquery',
      jQuery: 'jquery',
      'window.jQuery': 'jquery'
    }),
  ],
  optimization: {
    minimize: true,
    minimizer: [
      // enable the js minification plugin
        new TerserPlugin ({
            cache: true,
            parallel: true
        }),

      // enable the css minification plugin
      new OptimizeCSSAssetsPlugin({})
    ]
  }
};
