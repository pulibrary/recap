"use strict";

var gulp = require("gulp"),
  sass = require("gulp-sass"),
  autoprefixer = require("autoprefixer"),
  cssnano = require("cssnano"),
  postcss = require("gulp-postcss");

sass.compiler = require("node-sass");

gulp.task("clean", function() {
  return del([
      "css/pinwheel.css",
      "scripts/pinwheel.js"
  ]);
});

// Compiles Sass to CSS
gulp.task("sass", function() {
  return gulp
    .src("./src/sass/pinwheel.scss")
    .pipe(sass().on("error", sass.logError))
    .pipe(postcss([autoprefixer(), cssnano()]))
    .pipe(gulp.dest("./css"));
});

// Compiles JS
gulp.task("scripts", function() {
  return gulp.src("./src/scripts/pinwheel.js").pipe(gulp.dest("./scripts"));
});

// Watches for changes in Sass files
gulp.task("watch", function() {
  gulp.watch("./src/sass/**/*.scss", gulp.series("sass"));
  gulp.watch("./src/scripts/*.js", gulp.series("scripts"));
});

// Compiles SASS and scripts
gulp.task("compile", gulp.series(["sass", "scripts"]));