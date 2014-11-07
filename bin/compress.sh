#!/bin/bash
# Compresses JS using Closure Compiler, CSS using YUICompressor
# - call from CorA main directory (bash bin/compress.sh)
# - set pointers to JAR files as necessary
# - if present, minified files are preferred and loaded by CorA automatically

set -o errexit

CLOSURE_COMPILER=/home/bollmann/tools/webdev/closure-compiler/compiler.jar
YUI_COMPRESSOR=/home/bollmann/tools/webdev/yuicompressor-2.4.8.jar

echo "Minifying CSS..."
cd gui
cat css/screen.css css/baseBox.css js/mbox/assets/mBoxCore.css js/mbox/assets/mBoxModal.css js/mbox/assets/mBoxNotice.css js/mbox/assets/mBoxTooltip.css js/mbox/assets/mForm_mod.css js/mbox/assets/mFormElement-Select.css css/Meio.Autocomplete.css css/MultiSelect.css | sed "s#url('images/#url('../images/mbox/#" | java -jar $YUI_COMPRESSOR --type css -o css/master.min.css

echo "Minifying JavaScript..."
cd js
java -jar $CLOSURE_COMPILER --js_output_file=mbox.min.js mbox/mBox.Core.js mbox/mBox.Modal.js mbox/mBox.Notice.js mbox/mBox.Modal.Confirm.js mbox/mBox.Tooltip.js mbox/mForm.Core.js mbox/mForm.Submit.js mbox/mForm.Element.js mbox/mForm.Element.Select.js

java -jar $CLOSURE_COMPILER --js_output_file=master.min.js baseBox.js ProgressBar.js dragtable_hack.js iFrameFormRequest.js Meio.Autocomplete.js gui.js tagset.js file.js edit.js settings.js MultiSelect.js

java -jar $CLOSURE_COMPILER --js_output_file=admin.min.js datepicker.js admin.js

echo "Done!"
