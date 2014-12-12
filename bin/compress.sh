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
sass --scss css/screen.scss css/screen.css
cat css/screen.css css/baseBox.css js/mbox/assets/mBoxCore.css js/mbox/assets/mBoxModal.css js/mbox/assets/mBoxNotice.css js/mbox/assets/mBoxTooltip.css js/mbox/assets/mForm_mod.css js/mbox/assets/mFormElement-Select.css css/Meio.Autocomplete.css css/MultiSelect.css | sed "s#url('images/#url('../images/mbox/#" | java -jar $YUI_COMPRESSOR --type css -o css/master.min.css

echo "Minifying JavaScript..."
cd js
java -jar $CLOSURE_COMPILER --js_output_file=mbox.min.js mbox/mBox.Core.js mbox/mBox.Modal.js mbox/mBox.Notice.js mbox/mBox.Modal.Confirm.js mbox/mBox.Tooltip.js mbox/mForm.Core.js mbox/mForm.Submit.js mbox/mForm.Element.js mbox/mForm.Element.Select.js

java -jar $CLOSURE_COMPILER --js_output_file=master.min.js baseBox.js ProgressBar.js iFrameFormRequest.js Meio.Autocomplete.js gui.js tagsets.js tagsets/Tagset.js tagsets/SplitClassTagset.js tagsets/POS.js tagsets/Norm.js tagsets/NormBroad.js tagsets/NormType.js tagsets/LemmaAutocomplete.js tagsets/LemmaSugg.js tagsets/Lemma.js tagsets/LemmaPOS.js tagsets/Comment.js tagsets/TagsetFactory.js file.js edit/DataTableProgressBar.js edit/DataTableDropdownMenu.js edit/DataTable.js edit/FlagHandler.js edit/LineJumper.js edit/TokenSearcher.js edit/HorizontalTextPreview.js edit/PageModel.js edit/EditorModel.js edit.js settings.js MultiSelect.js

java -jar $CLOSURE_COMPILER --js_output_file=admin.min.js datepicker.js admin.js

echo "Done!"
