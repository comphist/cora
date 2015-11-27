Automatic annotation tools (part-of-speech taggers, lemmatizers, etc.) are often
used when producing gold-standard annotations: it is often faster --- and more
convenient --- to review and correct annotations created by a software tool than
it is to annotate everything from scratch.  However, for non-standard language
data (e.g. historical texts, less-resourced languages), suitably trained
annotation software often doesn't exist, so it might be desirable to (re-)train
an annotation program with new manually annotated data as it becomes available.

For this reason, CorA offers the functionality to embed automatic annotation
tools and call them directly from the GUI.

!!! note "Note"
    This is a very powerful feature, but it's also one of the less
    developed ones.  Depending on the software that you want to use, setting it
    up for use within CorA might require a bit of work and knowledge of PHP
    programming.  Also, many details of the implementation are still subject to
    change.

- - -

To manage external annotators, change to the tab "Administration" while logged
in to CorA with an administrator account.

+ **Create a new annotator** by clicking the "Add Tagger" button at the top of
  the page.  You will be prompted for the tagger's name first, and can edit
  individual settings afterwards.

You can manage existing annotators in the "Automatic Annotators" section of the
admin page.  Individual settings can be changed by clicking on "Options...",
namely:

+ **PHP class**: Embedding an external annotation tool requires a PHP class that
  acts as an interface between CorA and the external tool.  The name of the
  class goes in this field.  See below for
  [details on tagger interfaces](#tagger-interfaces).

+ **Options**: Enter an arbitrary list of key/value pairs as options for the PHP
  annotator class.  Which options are available depends entirely on the PHP
  class that you use.  Click on ![plus](img/icon-plus.png) or
  ![minus](img/icon-minus.png) to add or remove rows from the option list,
  respectively.

+ **Individually trained**: Check this box if the tagger should be (re-)trained
  on documents within CorA.  This requires the PHP class to support this, and
  the
  [configuration option "external_param_dir"](setup-config.md#list-of-configuration-options)
  to be set to an existing directory with read/write permissions for the web
  server process. Leave this unchecked if the tagger already is or does not need
  to be trained.

+ **Tagset links**: Specify which tagset(s) are required to be present for this
  tagger to work.  The tagger will be available for all documents that have at
  least the tagsets specified here associated with them.

By default, annotators will operate on the "ascii" form of the modernized tokens
(cf. [CorA's document model](document-model.md)).

## Tagger interfaces

As said above, embedding an external annotation tool requires a PHP class that
acts as an interface between CorA and the external tool.  Currently, the
following interfaces are already part of the CorA distribution, i.e. you can use
them "out-of-the-box":

Lemmatizer
:   Acts as a simple wrapper around a lemmatization script.  Supports the
    following options:

      + **bin**: path to the lemmatization script (**required**); see
        `bin/cora_lemmatize.perl` for an example script that can be used with
        this class
      + **filter_unknown** (boolean -- 0 or 1): if 1, filters annotations where
        the lemmatization script returned "<unknown>"; defaults to 1
      + **lowercase_all** (boolean -- 0 or 1): if 1, all input is lowercased;
        defaults to 0
      + **par**: path to the parameter file (required if the lemmatizer should
        *not* be individually trained)
      + **use_norm** (boolean -- 0 or 1): if 1, use the normalization as input;
        defaults to 0
      + **use_pos** (boolean -- 0 or 1): if 1, make use of POS annotation when
        lemmatizing; defaults to 1

RFTagger
:   Acts as a wrapper around [RFTagger][], a part-of-speech tagger developed by
    Helmut Schmid and Florian Laws.  Supports the following options:

      + **annotate**: path to the `rft-annotate` binary (**required**)
      + **flags**: additional flags for RFTagger; defaults to `-c 2 -q`
      + **lowercase_all** (boolean -- 0 or 1): if 1, all input is lowercased;
        defaults to 0
      + **minimum_span_size**: when re-training on a document that has "gaps" in
        the annotation, only chunks of continuously annotated tokens of at least
        this size are used in training; defaults to 5
      + **par**: path to the parameter file (required if the tagger should
        *not* be individually trained)
      + **train**: path to the `rft-train` binary (required if the tagger should
        be individually trained)
      + **use_layer**: whether to use 'ascii', 'trans', or 'utf' forms of token;
        can also be set to 'norm' to use the normalization as input; defaults to
        'ascii'
      + **wc**: path to RFTagger wordclass file (required)

DualRFTagger
:   Combines two instances of RFTagger: one using a provided parameter
    file; the other one individually trained.  During annotation, both instances
    are called.  The tag returned by the individually trained model is chosen if
    either (a) the input token was already seen during training (with a number
    of occurences greater than a certain threshold) and its tag is *not* "?"; or
    (b) the fixed model returned "?" as its tag. Otherwise, the tag from the
    fixed model is chosen.

    Supports the same options as RFTagger, and additionally:

      + **threshold**: the threshold for "trusting" the individually trained
        model; defaults to 1

### Writing your own interface

If you'd like to integrate some external tool that is not covered by one of the
interface classes above, you need to write your own PHP class for it.

All annotation interfaces reside in `<cora-dir>/lib/annotation/`.  There is a
base class "AutomaticAnnotator" (in `AutomaticAnnotator.php`) from which all
other interfaces should inherit.  Place your custom interface, say, "FooTagger",
in a file `FooTagger.php` in this directory.  You can then create a new
annotator in CorA with a PHP class of "FooTagger" which uses this interface.

Please refer to the code documentation for more details on what your custom
class should implement.



[rftagger]: http://www.cis.uni-muenchen.de/~schmid/tools/RFTagger/

