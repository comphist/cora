# Although we're not doing any minification yet, these source files are listed
# in the order required for this step

# should compile to master.min.js
# TODO: can we separate the "plugin" files from CorA proper?
add_sources(
  request/CoraRequestErrors.js
  request/CoraRequest.js
  settings.js
  ProgressBar.js
  iFrameFormRequest.js
  Meio.Autocomplete.js
  gui.js
  tagsets.js
  tagsets/Tagset.js
  tagsets/SplitClassTagset.js
  tagsets/POS.js
  tagsets/Norm.js
  tagsets/NormBroad.js
  tagsets/NormType.js
  tagsets/LemmaAutocomplete.js
  tagsets/LemmaSugg.js
  tagsets/Lemma.js
  tagsets/LemmaPOS.js
  tagsets/Boundary.js
  tagsets/Comment.js
  tagsets/TagsetFactory.js
  file.js
  edit/DataSource.js
  edit/DataTableNavigation.js
  edit/DataTableProgressBar.js
  edit/DataTableDropdownMenu.js
  edit/DataTable.js
  edit/FlagHandler.js
  edit/LineJumper.js
  edit/TokenSearcher.js
  edit/SearchResults.js
  edit/HorizontalTextPreview.js
  edit/PageModel.js
  edit/EditorModelUndo.js
  edit/EditorModel.js
  edit.js
  MultiSelect.js
)

# should compile to admin.min.js
add_sources(
  datepicker.js
  admin.js
)

# should compile to mbox.min.js
add_sources(
  mbox/mBox.Core.js
  mbox/mBox.Modal.js
  mbox/mBox.Notice.js
  mbox/mBox.Modal.Confirm.js
  mbox/mBox.Tooltip.js
  mbox/mForm.Core.js
  mbox/mForm.Submit.js
  mbox/mForm.Element.js
  mbox/mForm.Element.Select.js
)
