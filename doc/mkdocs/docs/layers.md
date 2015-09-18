CorA supports various types of annotations: part-of-speech tags, lemmatization,
normalization, etc. --- we refer to them as **annotation layers** (or,
sometimes, **tagsets**[^tagsets]).

Annotation layers have to be associated with (or "linked to") documents in order
to use them.  This way, you can customize which annotation columns should appear
in the editor.  You can also have multiple projects that use different layers,
different POS tagsets, etc.  These associations must be defined by an
administrator, either in the [project settings](admin-projects.md) or, if
needed, [for individual texts](doc-manage.md#tagset-links).

#### Open vs. closed layers

An important distinction is that between open- and closed-class annotation
layers:

+ **Open annotation layers** do not place restrictions on the possible
  annotation values.  In the editor, they are typically represented by a text
  box which accepts arbitrary input.

    Instances of these annotation layers are created automatically and can
    simply be selected when linking annotation layers to documents.

    Example: *lemmatization*

+ **Closed annotation layers** have a fixed set of allowed annotation values.
  In the editor, values are typically selected from a dropdown box.

    Because of this, instances of these annotation layers need to be [created
    manually in the "Administration" tab](admin-tagsets.md) by providing a
    name and a list of allowed values ('tags').  This also means that,
    contrary to open-class layers, there can be several instances of the same
    closed annotation layer, but with different tag lists.

    Example: *POS*

## List of annotation layers

Here is a list of all annotation layers that CorA currently supports.  Each
layer has an internal "type", i.e. a short abbreviation which identifies it.
This type string is used when [importing tagsets](admin-tagsets.md) and also
appears in XML tags when exporting documents in [CorA-XML format](coraxml.md).

Name                                  | Type           | Class
------------------------------------- | -------------- | ------------
[Part-of-speech](#part-of-speech-pos) | pos            | closed
[Lemmatization](#lemmatization)       | lemma          | open
[Lemma suggestion](#lemma-suggestion) | lemma_sugg     | closed
[Lemma part-of-speech](#lemma-part-of-speech) | lemmapos | closed
[Normalization](#normalization)       | norm           | open
[Modernization](#modernization)       | norm_broad     | open
[Modernization type](#modernization-type) | norm_type  | closed
[Boundaries](#boundaries)             | boundary       | closed
[Comments](#comments)                 | comment        | open
[Secondary comments](#comments)       | sec_comment    | open

### Part-of-speech (POS)

Part-of-speech annotation in form of a closed tagset, optionally containing
morphological information as well.  In the editor, this annotation layer is
represented by two separate columns: "POS" and "Morphology".  When making a
selection in the "POS" dropdown box, the contents of the "Morphology" dropdown
box change to only allow values that form legal tags with the chosen "POS"
entry.

However, it is important to note that these **two columns are conflated
internally**, and actually represent a single annotation of the value
"*<POS\>.<Morphology\>*" --- a **dot (.)** acts as a separator between the two.

In a tag value, everything preceding the first dot is considered to be the
(base) "POS" tag, while everything following it is treated as belonging to
"Morphology".  An exception is made for tags having a single dot at the end,
because some tagsets use tags like "*$.*" for punctuation.  Note that you
are *not required* to use morphological attributes in your tags --- it is
perfectly fine to use tagsets with only plain POS tags; the "Morphology" column
can be [conveniently hidden](doc-customize.md) in this case.

!!! attention "Attention"
    For historical reasons, each document is **required to have at least one
    part-of-speech tagset associated with it.** The web interface will currently
    not function correctly without one.  This requirement will likely be removed
    in future versions --- meanwhile, if you'd like to annotate a text without
    a POS tagset, the recommended workaround is to assign a "dummy tagset" and
    hide the POS-related columns in the editor settings.

### Lemmatization

Lemma annotation in form of an open tagset, represented by a text box in the
editor.

Lemmatization always comes with a **lemma verification checkbox** that turns
green when you click on it.  When entering a lemma, the system will search for
suggestions based on *identical tokens with a marked lemma verification
checkbox* in all texts within the current project, and display them in bold
green:

![](img/layers-lemma.png)
{: .figure .align-center}

Lemma entry with marked verification checkbox and a lemma suggestion
{: .figure-caption .align-center}

The verification checkbox becomes unchecked when you modify the lemma entry, and
becomes checked when you select a green (= verified) suggestion from the list.

Automatical lemma suggestions can also be provided by adding the
[lemma suggestion](#lemma-suggestion) annotation layer.

### Lemma suggestion

Provides auto-completion suggestions for [lemma annotations](#lemmatization).

Strictly speaking, this isn't an annotation layer at all; rather, it provides an
*additional resource* to another annotation layer, the
[lemmatization](#lemmatization) layer.  When using lemma suggestions, the lemma
text field will provide **auto-completion suggestions** to the user based on the
string that he entered and the values in the lemma suggestion "tagset".  A
maximum of twenty suggestions can be shown at a time.

![](img/layers-lemma_sugg.png)
{: .figure .align-center}

Auto-completion for lemmata based on a pre-defined list of lemma suggestions
{: .figure-caption .align-center}

In the auto-completion list, values in **square brackets** at the end of the
string are displayed in light grey; a possible use case for this is IDs
referring to an external lexicon.  Apart from the distinctive rendering, these
strings are not treated in any special way, though.

Special characters, such as accented letters, are conflated with their "simple"
counterparts to a certain degree,[^conflation] so that entering 'a' in the lemma
field would find auto-complete suggestions like 'à' or 'âme'.



[^tagsets]: This is for historical reasons: since POS annotation came first, and
it is common to speak of a "POS tagset", the term "tagset" has been generalized
to all annotation layers that were added afterwards.  Hence, "tagset" is
sometimes used even for annotations like lemmata or normalization (which do not
really have a tagset in the usual sense).

[^conflation]: To be precise, the character conflation happens in the database
query, possibly depending on the MySQL version on the server, according to
the ["utf8_general_ci"
collation chart](http://collation-charts.org/mysql60/mysql604.utf8_general_ci.european.html).

*[POS]: part-of-speech
