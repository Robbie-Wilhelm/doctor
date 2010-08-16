# doctor

doctor ist a little tool to generate book like 
(html) documents from a directory of markdown files.

## features

* automatic generation of table of contents (TOC)
* keep meta data in source files
* include files
* handle source code as includes nicely
* autoinclude via scan command
* flexible layouts

## depends on

* php
* Michel Fortin's markdownextra (included)
* Tobiasz Cudnik's phpQuery (included)

## install

* copy/clone/download
* link directory `public` somewhere in your webservers doc-root
    ln -s /the/path/of/doctor/public /var/www/localhost/htdocs/doctor
* in your browser: open `http://localhost/doctor/index.php/doctor`
* you should see this document

## usesage

all documents/ books should be placed or symlinked into the `books` folder.
documents consiting of multiple files should be placed into subdirectories.

### books from multiple files

* the root document must be named `index.md`
* if you want automaticly include files, you must name them `99_some_title.md` 
(2 digits followed by an underscore). this is for sorting your chapters

address your `book.md | book/index.md` via `doctor/index.php/[book]`

## metadata

your markdown file can have a header with certain metadata like:

		title: xorc Dokumentation
		author: Robbie Wilhelm
		doc-levels: h2,h3,h4
		doc-assets: /dev/doctor/books/xorc/assets/
		doc-css: css/md-style.css
		
the `doc-*` fields are for controlling doctor. 

## styling

change `default_layout.html` for your needs.


