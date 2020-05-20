const rtl = require('postcss-rtl');

module.exports = ctx => ({
  map: !ctx.env || ctx.env !== 'production' ? { inline: false } : false,
  plugins: [
    require('postcss-import'),
    require('postcss-nested'),
    require('postcss-custom-media'),
    require('postcss-custom-properties')({
      preserve: false,
    }),
    require('postcss-calc'),
    require('autoprefixer')({
      cascade: false,
      grid: 'no-autoplace',
    }),
    require('postcss-discard-comments'),
    rtl(),
  ]
});
