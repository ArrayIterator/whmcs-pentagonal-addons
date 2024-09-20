const TerserPlugin = require('terser-webpack-plugin');
const { DefinePlugin } = require('webpack');
const MiniCssExtractPlugin = require("mini-css-extract-plugin");
const CssMinimizerPlugin = require("css-minimizer-webpack-plugin");

const env = (process.env.NODE_ENV || 'production') === 'production' ? 'production' : 'development';
const isServe = process.env.WEBPACK_SERVE === 'true';
const isProduction = env === 'production';
const libBaseName = 'whmcs-pentagonal';
const resolvePath = (base) => {
    base = base.replace(/\\/g, '/').replace(/^\/|\/$/g, '');
    return (pathData) => {
        let dir = base + '/';
        let fileName = pathData.filename.replace(/\\/g, '/');
        if (fileName.startsWith('node_modules')) {
            dir += 'vendor/';
            let fNames = fileName.split('node_modules/');
            fileName = fNames.pop();
            return dir + fileName;
        }
        dir += 'core/';
        let relativePath = pathData.module.resourceResolveData.relativePath;
        relativePath = relativePath.replace(/\\/g, '/').replace(/^(\.)?\//g, '');
        if (relativePath.startsWith('src/')) {
            relativePath = relativePath.substring(4);
        }
        return dir + relativePath;
    }
}
// md5 random id
const id = Math.random().toString(36).substring(2, 15) + Math.random().toString(36).substring(2, 15);
const config = {
    mode : isProduction ? 'production' : 'development',
    entry : {
        runtime : './ui/ts/runtime.ts',
        pentagonal : {
            import : './ui/ts/pentagonal.ts',
            dependOn: 'runtime',
        },
    },
    output : {
        library : {
            name : {
                root : libBaseName,
                amd : `amd-${libBaseName}`,
                commonjs : `common-${libBaseName}`,
            },
            type : 'umd',
        },
        path : __dirname,
        filename : "assets/js/[name].js",
        hotUpdateChunkFilename: './.tmp/[name].hot-update.js',
        hotUpdateMainFilename: './.tmp/[runtime].hot-update.json'
    },
    watchOptions : {
        ignored : /(^[\/\\](node_modules|vendor|js|css|img|fonts)[\/\\]|\.(lock|md|log|php|ya?ml)$|(^|[\/\\])\.)/,
        aggregateTimeout : 300,
        poll : 1000,
        followSymlinks : true,
    },
    devServer : {
        hot : true,
        headers : {
            'Access-Control-Allow-Origin' : '*'
        },
        allowedHosts : ['all'],
        liveReload : false,
        devMiddleware: {
            publicPath: './',
            serverSideRender: true,
            writeToDisk: true
        },
        client: {
            webSocketURL: 'ws://localhost:9192/ws',
            overlay: {
                warnings: true,
                errors: true,
            },
        },
        port: 9192,
        webSocketServer: 'ws',
        watchFiles : [
            'ui/**/*.{ts,scss}',
        ],
    },
    devtool : isProduction ? false : 'source-map',
    resolve : {
        extensions : [
            '.js',
            '.jsx',
            '.ts',
            '.tsx',
            '.tpl',
            '.json'
        ],
    },
    plugins : [
        new DefinePlugin({
            'process': {
                env: {
                    BUILD_ID : JSON.stringify(id),
                    ENVIRONMENT : JSON.stringify(isProduction),
                }
            },
        }),
        new MiniCssExtractPlugin({
            filename : 'assets/css/[name].css',
        }),
        // new HotModuleReplacementPlugin()
    ],
    optimization : {
        minimize : isProduction,
        minimizer : [
            new TerserPlugin({
                parallel : true,
                terserOptions : {
                    compress : {
                        drop_console : false
                    },
                    output : {
                        comments : !isProduction,
                    },
                },
                extractComments : false,
            }),
            new CssMinimizerPlugin({
                minimizerOptions : {
                    preset : [
                        "default",
                        {
                            discardComments : {
                                removeAll : true
                            },
                        },
                    ],
                },
            }),
        ],
    },
    module : {
        rules : [
            {
                test : /\.ts?$/,
                use : 'ts-loader',
                exclude : /node_modules/,
            },
            {
                test : /\.tpl$/i,
                use : 'html-loader',
            },
            {
                test : /\.(eot|[to]tf|woff|woff2)$/i,
                type : 'asset/resource',
                generator : {
                    filename : resolvePath('fonts')
                },
            },
            {
                test : /\.(png|jpg|gif)$/i,
                type : 'asset/resource',
                generator : {
                    filename : resolvePath('img/images')
                }
            },
            {
                test : /\.(svg)$/i,
                type : 'asset/resource',
                generator : {
                    filename : resolvePath('img/svg')
                }
            },
            {
                test : /\.css$/i,
                use : [
                    MiniCssExtractPlugin.loader,
                    {
                        loader : 'css-loader',
                        options : {
                            sourceMap : !isProduction,
                        }
                    },
                    'postcss-loader'
                ],
            },
            {
                test : /\.(s[ac]ss)$/,
                use : [
                    MiniCssExtractPlugin.loader,
                    {
                        loader : 'css-loader',
                        options : {
                            sourceMap : !isProduction
                        }
                    },
                    'postcss-loader',
                    {
                        loader : 'sass-loader',
                        options : {
                            sassOptions : {
                                quietDeps : true
                            }
                        }
                    },
                ]
            },
            {
                test : /\.js$/i,
                exclude : /node_modules/,
                use : {
                    loader : 'babel-loader',
                    options : {
                        presets : ['@babel/preset-env']
                    }
                },
            },
        ],
    }
};
if (!isProduction && !isServe) {
    config.watch = true;
}

module.exports = () => config;