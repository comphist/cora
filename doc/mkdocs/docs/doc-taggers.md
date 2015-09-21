You can call external annotation tools directly from within CorA as long as
they've been [set up accordingly by an administrator](admin-taggers.md).  This
feature can be used, for example, to pre-annotate a text and then correct
these annotations manually.  If the annotation tool supports it, you can also
retrain it with data you've already annotated in CorA in order to improve the
results.

To call an external annotator, click on "Automatic annotation" in the toolbar of
the "Edit" tab while you've opened a document in CorA.  A dialog window will
open where you can choose from the available annotators.

!!! attention "Attention"
    Using this feature requires that an external annotator
    is [set up by an administrator](admin-taggers.md) first.  If you
    cannot see an annotation tool in the list that you expect to be there,
    please consult a local administrator.  Admins should make sure
    that annotators are actually linked to a tagset that the document uses.

## Performing an automatic annotation

To perform an automatic annotation, open the automatic annotation dialog, then
select one of the available annotators from the list and click "Annotate".
While the annotation process is running, the interface will be blocked and a
waiting screen will be shown.  The total duration of this process depends on the
annotation tool that is being used.

!!! danger "Danger"
    Calling an automatic annotator will **overwrite existing annotations** and
    currently **cannot be undone**.  You can, however, control what will get
    overwritten.  Read on for more details.

Automatic annotators will always respect the
[**green progress bar**](doc-annotate.md#the-editor-table) in the editor: tokens
in the range of the green progress bar will **not** be affected by the automatic
annotation, while all tokens with a gray progress bar will have their
annotations overwritten by the external tool.

Note that this *only* affects the annotations of the type the external annotator
uses: a pure part-of-speech annotator, for example, will not overwrite
annotations in the normalization column.

## Retraining an annotator

To retrain an automatic annotator on data in CorA, click the yellow "Retrain"
button in the automatic annotation dialog.  If the button is not available
(i.e. grayed out), the selected annotator cannot be retrained.

Retraining will cause the annotator's training functionality to be called, with
data from CorA as its input.  Typically, this data will not only be taken from
the currently opened document, but from **all documents within the same
project**.  The [green progress bar](doc-annotate.md#the-editor-table) is again
used as the criterion for this: only text passages marked in green will be added
to the training data.  This way, you can make sure that only manually verified
annotations will be used.

!!! note "Note"
    If you are using a retrainable annotator for the first time, it
    is likely that you need to train it once before you can use it for
    annotation.  If you get an error message during annotation with a
    retrainable annotator, try performing the retraining first.
    
