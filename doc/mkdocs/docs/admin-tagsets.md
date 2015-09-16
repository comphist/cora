Tagsets (or "annotation layers") can be of two types: *open* or *closed*.  In
open annotation layers, annotations can be of any value and are typically
entered in a text field.  Closed annotation layers, on the other hand, have a
list of allowed values (= a "tagset" in the traditional sense), which are
typically selected in a dropdown box --- part-of-speech tags are a common
example.  To use a closed annotation layer, you first need to provide this list
of allowed values.

- - -

To manage tagsets, change to the tab "Administration" while logged in to
CorA with an administrator account.

+ **Import a new tagset** by clicking the "Import Tagset" button at the top of
  the page.  You will be prompted for the tagset's name and class.  See the
  [section on annotation layers](layers.md) for a description of the various
  classes.

A tagset file is simply a **text file containing one tag per line.**

In a tagset file, **lines beginning with a caret (^)** have a special meaning:
the tag in this line will be marked as "needing correction".  This means that it
will not be available for selection by the user, and will be marked in red when
it occurs in a document.  However, it is still an accepted value when importing
or automatically annotating a document.  This way, old/deprecated/temporary tag
values can still be included, without users actually annotating with them.  The
caret (^) itself is not considered to be part of the tag, and a valid tag cannot
currently start with a caret when using this interface.

For part-of-speech tagsets (i.e. tagsets of type "pos"), dots (.) in tag values
are interpreted as attribute separators.  Everything preceding the first dot in
a line is considered to be the "base POS" tag, while everything following it is
considered to be morphological attributes.[^exception] Any given "base POS" tag
is required to **always have the same number of attributes.** This restriction
exists because some part-of-speech taggers mandate this, and violations of this
restriction often occur due to mistakes when compiling the tag list.

+ **View existing tagsets** by clicking the "Tagset Browser" button at the top
  of the page.  Here, you can select an imported part-of-speech tagset and
  retrieve some statistics, along with the full tag list, for it.

Browsing imported tagsets is currently very limited, and modifying existing
tagsets in any way via the user interface is currently not supported at all.
This functionality might be implemented at a later stage.


[^exception]: There is one exception to this rule: If a line contains exactly
one dot, and that dot is at the end of the line, it is considered to be part of
the "base POS" tag.  This is because some part-of-speech tagsets define tags for
punctuation that end on a dot (e.g., "$." in
[STTS](http://www.isocat.org/rest/dcs/376)).

*[POS]: part-of-speech

*[STTS]: Stuttgart-Tübingen-TagSet
