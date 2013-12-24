<!DOCTYPE html>
<html>
    <head>
        <title>CRUD Example</title>

        <link rel="stylesheet" type="text/css"
              href="style/jquery-ui-lightness/jquery-ui-1.10.3.custom.min.css"/>
        <link rel="stylesheet" type="text/css"
              href="vendor/cal127/crud/frontend/crud.css"/>
        <link rel="stylesheet" type="text/css"
              href="script/fancybox/jquery.fancybox.css"/>

        <script type="text/javascript" src="script/handlebars.js"></script>
        <script type="text/javascript" src="script/jquery-1.8.2.min.js"></script>
        <script type="text/javascript" src="script/jquery-ui-1.10.3.custom.min.js"></script>
        <script type="text/javascript" src="script/fancybox/jquery.fancybox.pack.js"></script>

        <script type="text/javascript" src="vendor/cal127/crud/frontend/crud.js"></script>
        <script type="text/javascript" src="vendor/cal127/crud/frontend/tpl.js"></script>

        <script type="text/javascript">
            $(function() {
                $('#crud').CRUD({
                    debug     : false,
                    path      : "/vendor/cal127/crud/frontend",
                    create_url: "/crud.php?op=create",
                    read_url  : "/crud.php?op=read",
                    update_url: "/crud.php?op=update",
                    delete_url: "/crud.php?op=delete",
                    upload_url: "/crud.php?op=upload"
                });
            });
        </script>
    </head>

    <body>
        <div id="crud" data='<?php print $crud; ?>' />
    </body>
</html>
