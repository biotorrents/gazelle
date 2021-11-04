#!/bin/bash

##
# Simple script to compile these assets:
#
#  - JS with Google Closure Compiler
#  - SCSS with SassC (Dart forthcoming)
#

# todo: Write tests for the Java environment and Google Closure Compiler binary
# todo: Rewrite the site JS to support --compilation_level ADVANCED_OPTIMIZATIONS
#[ ! -f './closure-compiler.jar' ] && echo "Please download Google Closure Compiler as $FILE from https://mvnrepository.com/artifact/com.google.javascript/closure-compiler"

# JavaScript
JS="./js/*.js"
for f in $JS
do
  echo ">>> Compiling $f..."
  basename=$(basename -s .js $f)
  java -jar closure-compiler.jar --js "./js/$basename.js" --js_output_file "../public/js/$basename.js"
done

# Cascading Style Sheets
SCSS="./scss/*.scss"
for f in $SCSS
do
  echo ">>> Compiling $f..."
  basename=$(basename -s .scss $f)
  sassc "./scss/$basename.scss" > "../public/css/$basename.css"
done
