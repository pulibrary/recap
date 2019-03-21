// Load in gulp
const gulp = require("gulp");

// Load in config JSON
const config = require("./build.config.json");
const autoprefixer = require("autoprefixer");
const browsersync = require("browser-sync").create();
const cssnano = require("cssnano");
const del = require("del");
const eyeglass = require("eyeglass");
const imagemin = require("gulp-imagemin");
const newer = require("gulp-newer");
const plumber = require("gulp-plumber");
const postcss = require("gulp-postcss");
const normalize = require("postcss-normalize");
const notify = require("gulp-notify");
const rename = require("gulp-rename");
const sass = require("gulp-sass");
const uglify = require("gulp-uglify");

// BrowserSync
function browserSync(done) {
  browsersync.init({
    proxy: "recap.test"
  });
  done();
}

// BrowserSync Reload
function browserSyncReload(done) {
  browsersync.reload();
  done();
}

// Removes existing assets
function clean() {
  return del([config.assets.dest]);
}

// Optimize Images
function images() {
  return gulp
    .src(config.images.files)
    .pipe(newer(config.images.dest)
    .pipe(
      imagemin([
        imagemin.gifsicle({ interlaced: true }),
        imagemin.jpegtran({ progressive: true }),
        imagemin.optipng({ optimizationLevel: 5 }),
        imagemin.svgo({
          plugins: [
            {
              removeViewBox: false,
              collapseGroups: true
            }
          ]
        })
      ]).on('error', notify.onError())
    )
    .pipe(gulp.dest(config.images.dest)));
}

// Compiles and minifies css (TODO: Add linter task)
function styles() {
  return gulp
    .src(config.styles.files)
    .pipe(plumber())
    .pipe(sass(
      eyeglass()
    ).on('error', notify.onError()))
    .pipe(gulp.dest(config.styles.dest))
    .pipe(rename({ suffix: ".min" }))
    .pipe(postcss([
      normalize({ forceImport: true, allowDuplicates: false, browsers: "last 2 versions" }),
      autoprefixer({ browsers: ["last 2 versions", "> 1%"] }),
      cssnano()
    ]))
    .pipe(gulp.dest(config.styles.dest))
    .pipe(browsersync.stream());
}

// Uglifies js
function scripts() {
  return gulp
    .src(config.scripts.files)
    .pipe(plumber())
    .pipe(uglify().on('error', notify.onError()))
    .pipe(rename("recap.scripts.min.js"))
    .pipe(gulp.dest(config.scripts.dest))
    .pipe(browsersync.stream());
}

// Watch files
function watchFiles() {
  gulp.watch(config.images.files, images);
  gulp.watch(config.styles.files, styles);
  gulp.watch(config.scripts.files, scripts);
  gulp.watch('**/*.{php,inc,info}',browserSyncReload);
}

// Complex tasks
const watch = gulp.parallel(watchFiles, browserSync);
const build = gulp.series(clean, gulp.parallel(images, styles, scripts));

exports.clean = clean;
exports.images = images;
exports.styles = styles;
exports.scripts = scripts;
exports.watch = watch;
exports.build = build;
exports.default = build;
