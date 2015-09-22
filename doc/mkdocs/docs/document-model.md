Understanding CorA's document model --- i.e., the logical representation of
documents within the software --- can be helpful for understanding certain
aspects of the tool better, and is crucially important when trying to import
your own texts into CorA.  The [CorA-XML file format](coraxml.md) closely
reflects this model.

The main design goal for this document model was to be suitable for historical
documents and other non-standard data.  It tries to address the problem of
[tokenization](#tokenization) by distinguishing between the appearance
in the original document and the desired units of annotation.  Furthermore, it
keeps information about the original [document layout](#layout).

## Tokenization

"Token" in a general sense is the name of a unit, usually roughly corresponding
to a "word", that is processed by an NLP tool.  When working with non-standard
data in particular, it is not always easy to define what a "token" should be.
Furthermore, the units that should be annotated might not be what is actually
found in the text (e.g., when separating by whitespace), for a variety of
reasons.

In CorA, **a token is a virtual unit.** It exists purely as a container for two
different types of tokenization:

+ **Diplomatic tokens** (sometimes called "dipl"s), which correspond to tokens as
  they appear in the source document.  [Layout information](#layout), which also
  relates to the document's appearance, refers to this type of tokens.  They are
  not currently visible in the web interface.

+ **Modernized tokens** (sometimes called "mod"s), which are the units of
  annotation.  They are displayed in the editor (each row in the editor
  corresponds to one modernized token) and only they can actually carry
  annotations.

The virtual "token" unit now acts as the **smallest overarching span** for any
set of diplomatic and modernized tokens.  This way, it connects the two
tokenization layers and allows inference about the relationship between
diplomatic and modernized tokens.

!!! note "Note"
    It's important to note that the difference between "diplomatic"
    and "modernized" tokens is purely one of **tokenization.**
    In particular, "modernized" in this sense does *not* mean that the form of
    the token (e.g. spelling) has been modified in any way.  The following
    examples might make this clearer.

### Some examples

In the simplest case, parent/diplomatic/modernized tokens are all identical.
It's not unusual if this is the case for the vast majority of tokens in your
document:

<table class="tokenization">
    <thead>
    <tr class="token-tok">
        <th class="label">parent token</th><th class="tok">das</th>
    </tr>
    </thead>
    <tbody>
    <tr class="token-dipl">
        <td class="label">diplomatic</td><td class="tok">das</td>
    </tr>
    <tr class="token-mod">
        <td class="label">modernized</td><td class="tok">das</td>
    </tr>
    </tbody>
</table>

For a more interesting example, let's take the example wordform "soltu", a
common word in historical German texts.  It can be analyzed as a contraction of
a verb and a pronoun, namely the modern German "sollst du" (lit. *"should
you"*).  Therefore, we decide to annotate these wordforms as two separate
tokens, "solt" and "u".[^1] However, we want to keep the information that, in
the source manuscript, "soltu" is a single word.  We could represent this in our
document model in the following way:

<table class="tokenization">
    <thead>
    <tr class="token-tok">
        <th class="label">parent token</th><th class="tok" colspan="2">soltu</th>
    </tr>
    </thead>
    <tbody>
    <tr class="token-dipl">
        <td class="label">diplomatic</td><td class="tok" colspan="2">soltu</td>
    </tr>
    <tr class="token-mod">
        <td class="label">modernized</td><td class="tok">solt</td><td class="tok">u</td>
    </tr>
    </tbody>
</table>

More complex cases are possible.  Let's take the (again, German) example "ober
czugemich", which would correspond to modern German "überzeuge mich"
(lit. *"convince me"*).  For some reason, the verb ("ober czuge") is separated
in the manuscript, but the pronoun ("mich") is attached to the verb.  This could
be due to mistakes by the manuscript writer, or simply lack of space, but in any
case we'd like to preserve that information, but still base our annotation on a
more semantically appropriate tokenization of this sequence.  In our document
model, this would look like:

<table class="tokenization">
    <thead>
    <tr class="token-tok">
        <th class="label">parent token</th><th class="tok" colspan="3">ober czugemich</th>
    </tr>
    </thead>
    <tbody>
    <tr class="token-dipl">
        <td class="label">diplomatic</td><td class="tok">ober</td><td class="tok" colspan="2">czugemich</td>
    </tr>
    <tr class="token-mod">
        <td class="label">modernized</td><td class="tok" colspan="2">oberczuge</td><td class="tok">mich</td>
    </tr>
    <tr style="visibility: hidden;">
        <td class="label"></td><td class="tok"></td><td class="tok"></td><td class="tok"></td>
    </tr>
    </tbody>
</table>

Here, the relationship between the modernized "oberczuge" and the diplomatic
tokens (i.e., that it consists of the first diplomatic token and parts of the
second) is not expressed directly, but only indirectly via their common "parent
token" span that contains them.

### Token representations

Apart from the different tokenization layers described above, there are
different *representations* of any given token as well.

+ **trans:** Originally for "transcription", this is the underlying base
  representation of a token.  It is one of the forms that can be displayed in
  the editor.  When [editing tokens](doc-edit.md), it is always the 'trans' form
  of the parent token that is edited, and the other representations must
  be *derivable* from that.

+ **utf:** Purely for viewing purposes, and one of the forms that can be
  displayed in the editor.  This field can be used for a proper Unicode
  representation of a token in case the 'trans' form encodes special
  characters in some way.

+ **ascii:** Only for modernized tokens, this field is the opposite of 'utf' in
  that it is intended to be a simple, ASCII representation of the token.  It is
  used as input for external annotation tools.

For example, if the transcription encodes the
["long s" character](http://www.fileformat.info/info/unicode/char/17f/index.htm)
'ſ' as '$' (e.g., for easier typing by the transcribers), a modernized token
could have the following representations:

<table class="tokenization">
    <thead>
        <tr>
            <th class="label">trans</th><th class="label">utf</th><th class="label">ascii</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td class="tok">$prach</td><td class="tok">ſprach</td><td class="tok">sprach</td>
        </tr>
    </tbody>
</table>

Here is an overview of which tokenization layers supports which representations:

<table class="tokenization">
    <thead>
        <tr>
            <th></th><th class="label">trans</th><th class="label">utf</th><th class="label">ascii</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td class="label">parent token</td><td>&#x2713;</td><td>&mdash;</td><td>&mdash;</td>
        </tr>
        <tr>
            <td class="label">diplomatic</td><td>&#x2713;</td><td>&#x2713;</td><td>&mdash;</td>
        </tr>
        <tr>
            <td class="label">modernized</td><td>&#x2713;</td><td>&#x2713;</td><td>&#x2713;</td>
        </tr>
    </tbody>
</table>



## Layout

This section is referenced in admin-projects.md.


[^1]: Of course, this is not the only way such cases can be handled.  We do not
claim that tokenizing the example in this fashion is the only, or even
the "best" way to do things --- the examples mainly serve to illustrate how
things would be represented in CorA *if* we want to analyze the data in the
described way.
