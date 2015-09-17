CorA (short for *Corpus Annotator*) is a web-based annotation tool for
non-standard language data.  To get the latest information about the tool,
please visit the
[CorA project website](http://www.linguistics.rub.de/comphist/resources/cora/).

This guide attempts to provide all the necessary information about CorA and how
to use it.

+ If you are a **server administrator wanting to run CorA on your own web
server**, you might want to start with the sections on
[installing](setup-install.md) and [administrating](admin-users.md) the tool.

+ If you are a **user working with an existing CorA installation**, you might
  want to jump straight to the
  [information on using the web interface](doc-annotate.md).

## Requirements

To run your own instance of CorA on a server machine, you need:

* A web server, such as [Apache](http://httpd.apache.org/)
* [PHP 5.3](http://www.php.net/) or newer, with the following extensions:
    * dom
    * json
    * libxml
* [MySQL 5.5](http://www.mysql.com/) or newer

To access an existing CorA instance on a web server, you only need:

* A web browser (we recommend [Chrome][], though
  [Firefox][] and [Safari][] should work as well)
    * JavaScript must be enabled
* An active internet connection

## License

**TODO**

## Contact

For any comments, questions, inquiries, etc., please contact Marcel Bollmann
(<bollmann@linguistics.rub.de>).

[chrome]: http://www.google.com/chrome/
[firefox]: http://www.mozilla.org/firefox/
[safari]: http://www.apple.com/safari/
