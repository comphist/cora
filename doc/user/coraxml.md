The CorA-XML file format is the primary format used to import/export documents
into/from CorA.  It is a serialization of the internal
[document model](document-model.md).  This section gives an informal description
of the format; a DTD is available in the repository.

The repository's `bin/` directory also contains a variety of scripts that
operate on CorA-XML files.

- - -

A valid CorA-XML document starts with the `<text>` root tag, and may contain (in
order):

+ A [`<cora-header>`](#cora-header) with internal attributes for the document.

+ A [`<header>`](#header) with textual information about the document.

+ A [`<layoutinfo>`](#layoutinfo) section serializing the
  document's [layout](document-model.md#layout).

+ A [`<shifttags>`](#shifttags) section serializing any [shift
  tags](document-model.md#shift-tags).

+ An ordered list of [`<token>`](#token) and [`<comment>`](#comment) elements
  representing the actual [tokens](document-model.md#tokenization) of the
  document, along with token-level comments.

All elements are optional except for a semantically
correct [layoutinfo](#layoutinfo) section and at least one [token](#token)
element.

## IDs and references

Many elements are required to have unique IDs assigned to them in an 'id'
attribute.  When exporting from CorA, IDs will use sequential numbers with a
prefix string for easier readability, but this is not a requirement --- IDs are
allowed to contain any unique string.

Likewise, many elements refer to others via a 'range' attribute.
The value of a 'range' attribute can take two different forms:

+ a single ID, such as "t2471"; or

+ an actual *range* of IDs, written as the IDs of the first and last elements in
  the range separated by two dots ('..'), e.g. "t2471..t2480".

Which elements are included in the range only depends on the **order of the
elements within the XML**, not on any property of the IDs themselves (such as
contained numbers).

## cora-header

`<cora-header>` may contain options that are usually set when [importing
a document](doc-manage.md#importing-a-document). It must be empty
except for a selection of the following attributes:

+ **name**: the document's name as it appears in CorA
+ **sigle**: the custom ID ('sigle') of the document

## header

The `<header>` element may contain text with meta information about the
document.  The content of this element is stored in CorA, but not parsed in any
way.  It can be viewed and edited by clicking on the "metadata" button in
the [editor toolbar](doc-annotate.md#the-toolbar).

## layoutinfo

A serialization of the document's layout; please refer to the [description of
layout information](document-model.md#layout) for details about
the semantics.

+ `<page>` elements correspond to **pages** and may have 'side', 'range', and
  'no' attributes.
+ `<column>` elements correspond to **columns** and may have a 'name' attribute.
+ `<line>` elements correspond to **lines** and may have a 'name' attribute.

All layout elements **must have** unique IDs ('id') and [a
'range' attribute](#ids-and-references).  The range attribute is an ID
reference, following the hierarchy laid out in the [layout
description](document-model.md#layout): pages must refer to columns,
which must refer to lines, which must refer to diplomatic tokens (`<dipl>`
elements; [see below](#tokens)).

An example of a trivial layoutinfo section --- representing a document with one
page, one column, and three lines --- could look like this:

```xml
<layoutinfo>
    <page id="p1" side="r" no="1" range="c1"/>
    <column id="c1" range="l1..l3"/>
    <line id="l1" name="01" range="t1_d1..t4_d1"/>
    <line id="l2" name="02" range="t5_d1..t12_d2"/>
    <line id="l3" name="03" range="t13_d1..t20_d1"/>
</layoutinfo>
```

## shifttags

A serialization of [shift tags](document-model.md#shift-tags); may contain any
number of the following elements, which must all be empty except for
[a 'range' attribute](#ids-and-references) refering to `<token>` elements
([see below](#tokens)):

+ `<fm>` for foreign-language passages
+ `<lat>` for Latin passages
+ `<marg>` for text on the page margins
+ `<rub>` for rubricized letters
+ `<title>` to mark page or section titles

## token

Each `<token>` element represented a [virtual "token"
unit](document-model.md#tokenization) in the document.  Tokens
are required to have an 'id' and a 'trans' attribute, and may
contain [diplomatic and modernized tokens](document-model.md#tokenization)
in form of the following elements:

+ `<dipl>` represents a **diplomatic token,** and must have 'id', 'trans',
  and 'utf' attributes.

+ `<mod>` represents a **modernized token,** and must have 'id', 'trans', 'utf',
  and 'ascii' attributes.

    They may also contain child elements with **annotations** in the following
    form:

    + `<{annotype} tag="value"/>` is the standard form for annotations,
      where `{annotype}` may be any [type abbreviation of an
      annotation layer](layers.md#list-of-annotation-layers), and `value` is
      the value of this annotation layer.  Each annotation type should usually
      only be represented once.

    + `<cora-flag name="{flagname}"/>` signals that a flag is set, where
      `{flagname}` may be any [valid flag name](layers.md#list-of-flags).

    + `<suggestions>` may contain any number of other annotation elements as
      child elements, and signals that these annotations should only be treated
      as suggestions, e.g. by an automatic annotation tool.  Support for this
      feature is currently very limited and basically restricted to
      part-of-speech annotation.

Here is a possible serialization of the [second
tokenization example](document-model.md#some-examples):

```xml
<token id="t42" trans="$oltu">
    <dipl id="t42_d1" trans="$oltu" utf="ſoltu"/>
    <mod id="t42_m1" trans="$olt" utf="ſolt" ascii="solt">
        <pos tag="VVFIN"/>
        <lemma tag="sollen"/>
        <cora-flag name="lemma verified"/>
    </mod>
    <mod id="t42_m2" trans="u" utf="u" ascii="u">
        <pos tag="PPER"/>
    </mod>
</token>
```

## comment

Each `<comment>` element represents a [token-level
comment](document-model.md#token-level-comments).  They are inserted
between `<token>` elements in the XML, must have a 'type' attribute with a
one-character value, and contain their value as text; for example:

```xml
<comment type="K">You probably shouldn't use these unless absolutely necessary.</comment>
```
