Projects act like a folder or container for documents.  Before you can import a
document, you need to create at least one project.  User access rights, default
tagsets used by contained documents, etc. are all configured on a per-project
level.

- - -

To manage projects, change to the tab "Administration" while logged in to
CorA with an administrator account.

+ **Create a new project** by clicking the "Add Project" button at the top of
  the page.  You will be prompted for a name for the project, and can edit
  individual settings afterwards.

You can manage existing projects in the "Project Management" section of the
admin page.  Individual settings can be changed by clicking on "Edit
settings...", namely:

+ **Script for editing tokens**: The command to be run whenever a token
  is edited; see [the section on token editing](edittoken.md) for details.

+ **Script for importing new texts**: The command used for importing new
  documents in a project-specific file format; see **TODO!** for details.

+ **User associations**: Select all users that should have access to this
  project.  There is currently no fine-grained rights management &mdash; anyone
  with access to a project has read/write access to all files contained within.

+ **Default tagset associations**: Select all tagsets to be linked to new
  imported texts in this project.  Note that this affects only *new* imported
  texts; to change tagset associations for existing texts, find the text in the
  "File" tab, then click on "Tagsets..." to view and/or change all associated
  tagsets.

**IMPORTANT:** When associating texts with tagsets, make absolutely sure that
  your selection includes 1) a maximum of *one* tagset for each *type*; and 2)
  exactly *one* tagset of type 'pos'.  *(The second restriction exists for
  historical reasons, and might be dropped someday.)* The system currently does
  not prevent you from creating associations that violate these criteria, but
  CorA will not work properly otherwise.  To learn more about tagsets and what
  the different types mean, refer to
  [the section on annotation layers](layers.md).

