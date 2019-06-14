const gulp = require('gulp');
const sass = require('gulp-sass');
const sassImportOnce = require('gulp-sass-import-once');
const postcss = require('gulp-postcss');
const postcssPresetEnv = require('postcss-preset-env');
const rtl = require('postcss-rtl');
const sourcemaps = require('gulp-sourcemaps');
const babel = require('gulp-babel');
const concat = require('gulp-concat');
const uglify = require('gulp-uglify');
const imagemin = require('gulp-imagemin');
const svgSprite = require('gulp-svg-sprite');

const pump = require('pump');
const cache = require('gulp-cached');

const cssnano = require('cssnano');

const plugins = [
  rtl(),
  postcssPresetEnv(),
  cssnano()
];

// More complex configuration example
const spriteConfig = {
  shape: {
    dimension: { // Set maximum dimensions
      maxWidth: 32,
      maxHeight: 32
    }
  },
  mode: {
    symbol: { // Activate the «symbol» mode
    }
  }
};

// Update Foundation with Bower and save to /vendor
gulp.task('bower', function() {
  return bower({ cmd: 'update'})
    .pipe(gulp.dest('vendor/'))
});

gulp.task('css', (cb) => {
  pump([
    gulp.src(['./src/**/*.scss', '!./src/bootstrap-scss/**/*.scss']),
    sourcemaps.init(),
    sass(),
    postcss(plugins),
    sourcemaps.write('.'),
    gulp.dest('./dist'),
  ], cb);
});

gulp.task('js', (cb) => {
  pump([
    gulp.src('./src/components/**/*.js', {
      base: './src/components'
    }),
    sourcemaps.init(),
    babel(),
    uglify(),
    sourcemaps.write('.'),
    gulp.dest('./dist/components')
  ], cb);
});

gulp.task('bootstrap:css', (cb) => {
  pump([
    gulp.src('./src/bootstrap-scss/style.scss', {
      base: './src/bootstrap-scss'
    }),
    sourcemaps.init(),
    sass(),
    postcss(plugins),
    sourcemaps.write('.'),
    gulp.dest('./dist/bootstrap/overrides'),
  ], cb);
});

gulp.task('bootstrap:js', (cb) => {
  pump([
    gulp.src([
      './bootstrap/assets/javascripts/bootstrap/transition.js',
      './bootstrap/assets/javascripts/bootstrap/alert.js',
      './bootstrap/assets/javascripts/bootstrap/button.js',
      './bootstrap/assets/javascripts/bootstrap/carousel.js',
      './bootstrap/assets/javascripts/bootstrap/collapse.js',
      './bootstrap/assets/javascripts/bootstrap/dropdown.js',
      './bootstrap/assets/javascripts/bootstrap/modal.js',
      './bootstrap/assets/javascripts/bootstrap/tab.js',
      './bootstrap/assets/javascripts/bootstrap/affix.js',
      './bootstrap/assets/javascripts/bootstrap/scrollspy.js',
      './bootstrap/assets/javascripts/bootstrap/tooltip.js',
      './bootstrap/assets/javascripts/bootstrap/popover.js'
    ]),
    babel(),
    concat('bootstrap.js'),
    uglify(),
    gulp.dest('./dist/bootstrap/overrides')
  ], cb);
});

gulp.task('postcss', (cb) => {
  pump([
    gulp.src('./dist/**/*.css'),
    cache('postcss'),
    postcss(plugins),
    gulp.dest('./dist')
  ], cb);
});

gulp.task('uglify', (cb) => {
  pump([
    gulp.src('./dist/**/*.js'),
    cache('uglify'),
    uglify(),
    gulp.dest('./dist')
  ], cb);
});

gulp.task('imagemin', (cb) => {
  pump([
    gulp.src('./src/images/**/*', '!./src/images/**/*.svg'),
    cache('imagemin'),
    imagemin(),
    gulp.dest('./dist/images')
  ], cb);
});

gulp.task('sprite', (cb) => {
  pump([
    gulp.src('./src/images/**/*.svg'),
    svgSprite(spriteConfig),
    gulp.dest('./dist/images')
  ], cb)
});

gulp.task('watch', () => {
  gulp.watch(
    [
      './src/bootstrap-scss/**/*.scss',
      '!./src/_colors.scss',
      '!./src/_utilities.scss'
     ],
     gulp.series('bootstrap:css'));

  gulp.watch(
    [
      './src/**/*.scss',
      '!./src/bootstrap-scss/**/*.scss',
      '!./src/_colors.scss',
      '!./src/_utilities.scss',
      '!./src/_mixins.scss'
    ], gulp.series('css'));

  gulp.watch(
    [
      './src/_colors.scss',
      './src/_utilities.scss',
      './src/_mixins.scss'
    ], gulp.series('bootstrap:css', 'css'));

  gulp.watch(['./src/components/**/*.js'], gulp.series('js'));

  gulp.watch(['./src/images/**/*', '!./src/images/**/*.svg'], gulp.series('imagemin'));

  gulp.watch(['./src/images/**/*.svg'], gulp.series('sprite'));

});

gulp.task('bootstrap', gulp.series('bootstrap:css', 'bootstrap:js'));

gulp.task('theme', gulp.series('css', 'js'));

gulp.task('build', gulp.series('bootstrap', 'theme', 'imagemin', 'sprite'));

gulp.task('default', gulp.series('build', 'watch'));
