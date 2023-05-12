#!/bin/bash


##
# compile static assets
#

# cascading style sheets
scss="./scss/*.scss"
for f in $scss
do
  echo ">>> compiling $f..."
  basename=$(basename -s .scss $f)
  sass "./scss/$basename.scss" > "../public/css/$basename.css"
done

# javascript
js="./js/*.js"
for f in $js
do
  echo ">>> compiling $f..."
  basename=$(basename -s .js $f)
  java -jar closureCompiler.jar \
    --compilation_level SIMPLE_OPTIMIZATIONS \
    --js "./js/$basename.js" \
    --js_output_file "../public/js/$basename.js"
done
