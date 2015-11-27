Sometimes, mistakes in the original text (e.g., typos by the transcribers of a
historical document) are only noticed during annotation.  In these cases, CorA
allows you to make edits to the tokens themselves.  Three edit operations are
available: editing (modifying), adding, and deleting a token.  They can all be
accessed by clicking on ![the dropdown icon](img/icon-dropdown.png) in the
respective row in the editor.

!!! note "Note"
    To use this feature, a
    [token editing script](admin-projects.md#setting-a-token-editing-script)
    must first be defined by an administrator in the
    [project settings](admin-projects.md).

!!! danger "Danger"
    All edit operations currently **cannot be undone** and should
    therefore be used with caution!

### Editing (modifying) a token

To edit an existing token, choose "Edit token..." from the dropdown menu
(![the dropdown icon](img/icon-dropdown.png)) in the editor.  Alternatively,
simply double-click on the token itself.

A dialog window will pop up and prompt you to modify the token, then confirm
your changes.  The new token is then processed by
[a server-side script](admin-projects.md#setting-a-token-editing-script), and
the editor view is updated to reflect the changes.  Existing annotations will be
retained if possible.

Note that you can only edit **a single token at a time!** In particular, you
**should not** include spaces etc. in the new token string to indicate token
boundaries. If you want to split up an existing token --- say, "foobar"
--- into two separate tokens "foo" and "bar", the correct way is to add a
new token "foo", then change the existing token "foobar" to "bar".

It is possible that the server-side script rejects the changes; e.g., it could
validate the string that you entered according to some internal criteria.  This
is completely up to your local administrators who set up the project and the
token editing script, and you should refer to them for any questions/problems
regarding this.

### Adding a new token

To add a new token, choose "Add token..." from the dropdown menu
(![the dropdown icon](img/icon-dropdown.png)) in the editor.

A dialog window will pop up and prompt you to enter the new token; this works
the same way as editing an existing token.  The new token will be added *before*
the token where you selected the "Add token..." option, but the dialog window
will also inform you about this.

### Deleting a token

To delete a token, choose "Delete token" from the dropdown menu
(![the dropdown icon](img/icon-dropdown.png)) in the editor.

You will be prompted for confirmation before the token is actually deleted.  Be
aware that deleting a token **loses information permanently** and currently
**cannot be undone** automatically.  *(Of course, you could always re-insert the
token manually via the "Add token" functionality.)*
