# About the user documentation

When writing/editing documentation for mkdocs, note that line breaks are usually
treated as spaces by Markdown, but mkdocs often omits these spaces if the next
line begins with a special markup element.  Therefore, instead of writing:

This is some documentation
[with a fancy link](iamfancy.html)

Make sure you wrap your lines like this:

This is some documentation [with
a fancy link](iamfancy.html)

Or this:

This is some
documentation [with a fancy link](iamfancy.html)
