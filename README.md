# PHP CRUD for Paris ORM
This is a CRUD application for PHP's Paris ORM.
It is analogue to Codeigniter 1's Grocery CRUD and Django's Admin.

## Features
- No scaffolding, works with a single function call. (After setting up)
- Handles relationships (joins)
- Fully customizable front-end with Handlebars templates
- Pre/post hooks for each operation
- Works with AJAX
- File uploading

## Backend Dependencies
- PHP 5.3.0 or newer
- Paris ORM

## Frontend Dependencies
- Handlebars
- jQuery
- jQuery UI
    - Uses only 'tabs' and 'datepicker', so you can build your own package
      containing only those
- Fancybox

## Usage
Full documentation will be available soon. For now you can refer to the
demo in the demo folder for usage. However, here are the most important
points to keep in mind:

- Use CRUD\Model instead of Paris' default \Model
  in all your code related with CRUD. (This includes model definitions)

- Define Paris models for each table and related table you want to CRUDify.
  Define all relationships you want to use with CRUD interface.

- Comply with CRUD conventions when defining relation functions
  as their body is parsed by CRUD. (These conventions are not documented yet.
  Please refer to the demo.)

- Create pages to handle get, post, put, delete and upload requests
  and from these pages call CRUD's get, post, put, delete and upload functions.
  (This may look awkward, but it is needed as CRUD's backend and frontend
  are fully separated.) Check out the demo.
