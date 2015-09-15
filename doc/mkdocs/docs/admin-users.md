Everyone who wants to access your CorA instance needs their own user account.
Currently, user accounts need to be created individually by an administrator
&mdash; is not possible for new users to create an account themselves.

**IMPORTANT:** Do not share accounts!  In particular, do not use the same
  account simultaneously on multiple machines or in multiple browsers!  Doing so
  can lead to erratic and unexpected behaviour.

- - -

To manage user accounts, change to the tab "Administration" while logged in to
CorA with an administrator account.

+ **Create a new user account** by clicking the "Add User" button at the top of
  the page.  The system will prompt for a username and password (the latter of
  which can be changed by the user once he logs in).

You can manage existing users in the "User Management" section of the admin
page.  You can **sort the table** by clicking on the headers, e.g. to quickly
see which users have logged in recently.

Here, you can:

+ **View last activity** and **currently opened document** for each user. *Last
  activity* is the time of the most recent server request by a given user; this
  means that the user has CorA opened in his/her browser and is logged in, but
  doesn't necessarily mean that the user is actually actively
  working within CorA.

+ **Toggle administrator rights** for a user.  If an account has administrator
  access, a ![green checkmark](img/icon-checkmark.png) appears in the "Admin"
  column.  Click on it or on the empty space to toggle administrator rights.
  *(Only administrator accounts can access content on the "Administration" tab,
  and create/manage other user accounts, projects, tagsets, external annotators,
  and server notifications.  Additionally, administrator accounts can **always**
  access **all** projects and texts, regardless of the project-specific access
  settings.)*

+ **Change e-mail addresses, notes, and passwords** for users by clicking on
  "Options...".  The e-mail address is currently *not* used by CorA in any way,
  but this might be implemented at a later stage.  Likewise, setting a "note"
  for a user is purely for informational purposes.  Users cannot see the notes
  you've assigned to them.

+ **Delete a user account** by clicking on ![a red 'X'](img/icon-delete.png) at
  the end of the row.  Be careful, this action currently cannot be undone!

If you want your users to be able to do anything meaningful in CorA, you have to
[assign them to projects](admin-projects.md) first.  Users will only able to
view or edit documents in projects that they've been assigned to.
