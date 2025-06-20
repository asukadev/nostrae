const Encore = require('@symfony/webpack-encore');

// Manually configure the runtime environment if not already configured yet by the "encore" command.
// It's useful when you use tools that rely on webpack.config.js file.
if (!Encore.isRuntimeEnvironmentConfigured()) {
    Encore.configureRuntimeEnvironment(process.env.NODE_ENV || 'dev');
}

Encore
    .setOutputPath('public/build/')
    .setPublicPath('/build')
    
    .addEntry('app', './assets/app.js')

    .enableStimulusBridge('./assets/controllers.json')

    .splitEntryChunks()
    .enableSingleRuntimeChunk()

    .cleanupOutputBeforeBuild()
    .enableBuildNotifications()
    .enableSourceMaps(!Encore.isProduction())
    .enableVersioning(Encore.isProduction())

    .enableSassLoader(function(options) {
    // add or set custom options
        options.sassOptions = {
      silenceDeprecations: ['mixed-decls', 'color-functions', 'global-builtin', 'import'],
    }
    })
;

module.exports = Encore.getWebpackConfig();