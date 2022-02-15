const path = require("path");
const DependencyExtractionWebpackPlugin = require("@wordpress/dependency-extraction-webpack-plugin");
const WooCommerceDependencyExtractionWebpackPlugin = require("@woocommerce/dependency-extraction-webpack-plugin");
const TerserPlugin = require("terser-webpack-plugin");
const MiniCssExtractPlugin = require("mini-css-extract-plugin");
const CssMinimizerPlugin = require("css-minimizer-webpack-plugin");
const { NODE_ENV = "production" } = process.env;

module.exports = {
  entry: "./src/index.tsx",
  mode: NODE_ENV,
  watch: NODE_ENV === "development",
  module: {
    rules: [
      {
        test: /\.(t|j)sx?$/,
        exclude: [/node_modules(\/|\\)(?!(debug))/, /build/, /build-module/],
        use: {
          loader: "babel-loader",
          options: {
            presets: [
              "@wordpress/babel-preset-default",
              [
                "@babel/preset-env",
                {
                  corejs: "3",
                  useBuiltIns: "usage",
                },
              ],
              ["@babel/preset-typescript"],
            ],
          },
        },
      },
      {
        test: /\.scss$/,
        use: [MiniCssExtractPlugin.loader, "css-loader", "sass-loader"],
      },
    ],
  },
  resolve: {
    extensions: [".tsx", ".ts", ".js"],
  },
  output: {
    filename: "acfw-wc-admin.js",
    path: path.resolve(__dirname, "dist"),
  },
  optimization: {
    minimizer: [new CssMinimizerPlugin({}), new TerserPlugin()],
  },
  plugins: [
    new DependencyExtractionWebpackPlugin(),
    new WooCommerceDependencyExtractionWebpackPlugin(),
    new TerserPlugin(),
    new MiniCssExtractPlugin({ filename: "acfw-wc-admin.css" }),
  ],
};
