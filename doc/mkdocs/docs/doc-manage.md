To manage documents, change to the tab "File" while logged in to CorA.

On this tab, you can see all projects that you have access to, and all files
(documents) that are contained within them.  You can:

+ **Open any document** by clicking on it.  Documents can only be opened by one
  user at a time, and each user can have only one opened document.  Documents
  that are currently opened are shown in *grey;* clicking on them will tell you
  details about the user who opened it.

+ **Sort the list of documents** by clicking on any of the headers --- e.g.,
  click on "Last Modified" to sort files by the time they were last modified.
  Click twice to sort in reverse order.

+ **Delete a document** by clicking on the ![red 'X'](img/icon-delete.png)
  symbol at the end of the row.  *Only the user who imported a text (shown under
  "Created... by") is allowed to delete it.* If you do not see the icon, you're
  probably not the user who created the file.  Be careful; this action currently
  **cannot be undone!**

!!! note "Note"
    Administrators can always see *all* projects and documents (regardless of
    the project's access settings), can always delete *any* file, and can
    forcibly close files opened by other users.

## Importing a document

There are two ways to import documents into CorA, both of which can be accessed
by clicking on the respective button at the top of the page:

1. **Import from CorA-XML**: Upload a file in [CorA-XML format](coraxml.md).

2. **Import from Custom Format**: Upload a file in a format specific to the
   project you're working on; this can be anything, and requires that an
   administrator has set up an appropriate
   [import script for the project](admin-projects.md#setting-an-import-script).

In both cases, you will be prompted for information about your document:

+ The **project** into which your document should be imported.
+ A **name** for your document.
+ (Optionally) a **custom ID** for your document.

!!! note "Note"
    Administrators can also **specify the tagset links** for the
    document; normal users cannot, but instead have to rely on the
    [default tagset associations](admin-projects.md) defined in the
    [project's settings](admin-projects.md).

    Furthermore, administrators can **view and change the tagset links** of
    existing documents by clicking on "Tagsets..." in the document list.

## Exporting a document

You can export any document by clicking on "Export..." in the document list.
The following export formats are available:

1. **Export as CorA-XML**: The recommended export format, since it contains all
   data for a document and can also be used to re-import it into CorA.

2. **Export as CSV**: A text file containing one (modernized) token per line,
   with customizable fields as character-separated values (CSV).  Good for
   readability, can easily be processed further or loaded into a spreadsheet
   application, but only contains a subset of the document's information.

A feature to export in a custom format, analogous to "Import from Custom
Format", is planned but not yet available.

*(Administrators can also choose to "Export as four-column normalization
 format", which only exists for legacy reasons and might be removed at any time.
 It is functionally almost identical to "Export as CSV" with columns "Token
 (ASCII)", "Normalization", "Modernization", "Modernization Type".)*
