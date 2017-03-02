Searching within a document allows you to quickly find tokens that match certain
criteria.  Search queries can currently only by executed within an opened
document; there is no functionality to search across multiple documents yet.

To open the search dialog, click on "Search" in the toolbar of the "Edit" tab
while you've opened a document in CorA.  Alternatively, for any token in the
editor, open the dropdown menu (![dropdown icon](img/icon-dropdown.png)) and
choose "Search for similar..." to open the dialog pre-filled with
the annotations of the respective token.

## Defining a search query

In the search dialog, you can define conditions that must apply to a token for
it to match the search query.

+ Specify if a token must match **all** or **any** (i.e., at least one) of these
  conditions via the dropdown box at the top.

+ Add or remove conditions from the list by clicking on
  ![plus](img/icon-plus.png) or ![minus](img/icon-minus.png), respectively.

+ Click the "Reset" button to quickly clear all conditions.

Each condition is made up of a *search field*, a *matching criterion*, and
(optionally) a *value*.

**Search fields** mainly correspond to annotation layers or flags within the
document, and should be self-explanatory.  Two fields deserve some further
explanation:

+ **Token:** Searches for modernized tokens in all representations, i.e.,
  "trans", "utf", and "ascii".  Alternatively, you can restrict the search to
  the "trans" representation by choosing "Token (Transcription)" instead.

+ **POS:** Searches for the *combined* part-of-speech and morphology tag; e.g.,
  to find a token with POS tag "VVIMP" and morphology "Sg", you need to enter
  "VVIMP.Sg" as the search value.

**Matching criteria** define the relation between the search field and its
value, and should also be mostly self-explanatory.  The criterion "matches
regex" can be used if you are familiar with [regular
expressions](https://en.wikipedia.org/wiki/Regular_expression).  CorA
relies on the [MySQL implementation of regular
expressions](https://dev.mysql.com/doc/refman/5.5/en/regexp.html#idm140174496723632)
for this feature, which uses [POSIX Extended Regular Expression
(ERE)](https://en.wikipedia.org/wiki/Regular_expression#POSIX_basic_and_extended)
syntax.

The **value** of the search condition is always *case-insensitive*.  It can be
left empty to specifically search for fields with empty or non-empty values.

!!! note "Note"
    All search conditions always refer to a single token; there is
    currently no way to define criteria based on a token's context,
    such as properties of a preceding token.

## Browsing search results

When you perform a search, a new tab "Search" becomes available, which contains
a list of all search results.

At the top of this page, you'll find a summary of the search, including
the **number of matching tokens** and a **list of the search criteria** for
reference.  In the toolbar, click the button "Modify search" to bring up the
search dialog again.

Search results are presented in a table which looks and behaves almost exactly
like the [editor table](doc-annotate.md#the-editor-table).  That means you
can **modify annotations** directly within this list, just like you would in the
editor.  The only differences to the actual editor are:

+ You can click on any token or its line number to **jump directly to this token
  in the main editor**.  This can be used, for example, to see the context of a
  token in the text.

+ Rows are more visually separated (to indicate that you're not viewing a
  continuous flow of text) and the dropdown menu
  (![dropdown icon](img/icon-dropdown.png)) is not available.

You can also **navigate search results within the editor**, without the need to
constantly switch back to the "Search" tab, by using the
buttons ![](img/editor-toolbar-search-prev.png){: .inline}
and ![](img/editor-toolbar-search-next.png){: .inline} in the editor's toolbar.
This will jump to the previous or next search result in the list, starting from
either the first result, or the one you last clicked on in the "Search" tab.


*[POS]: part-of-speech
