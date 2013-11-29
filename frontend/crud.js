$.fn.CRUD = function(settings) {
    if (!this.attr('data')) { return; }

    // Setup options
    // Defaults
    CRUD.settings = {
        debug       : false,
        path        : "/3rd/crud",
        get_url     : "/ajax/get/",
        post_url    : "/ajax/post/",
        put_url     : "/ajax/put/",
        delete_url  : "/ajax/delete/",
        upload_url  : "/ajax/upload/"
    }

    // Override defaults
    for (var i in settings) {
        CRUD.settings[i] = settings[i];
    }

    // Setup handlebars and helpers
    // TODO
    // This would be called twice if CRUD is called on more
    // than once in the same page. Implement a singleton-like solution
    CRUD.setupHandlebars();
    CRUD.setupHelpers();

    // Fetch data from elem
    var data = eval('(' + this.attr('data') + ')');
    this.removeAttr('data');
    this.addClass('crud');

    // Setup elements
    if (data.page_type == "list") {
        new CRUDList(this, data).render();
    } else if (data.page_type == "detail") {
        new CRUDDetail(this, data).render();

        for (rel_name in data.rels) {
            var sub_container = this.find('#rel_' + rel_name);
            new CRUDList(sub_container, data.rels[rel_name]).render();
        }
    }

    // Setup interface
    CRUD.setupInterface();
}


function CRUD(container, options)
{
    this.container = container;
    this.options = options;
}


CRUD.prototype.render = function()
{
    if (!this.options.page_type) { this.options.page_type = 'list'; }

    this.container.append(
        Handlebars.templates[this.options.page_type](this.options)
    );
}


function CRUDList(container, options)
{
    CRUD.call(this, container, options);

    // State
    this.hash = options.hash;
    this.limit = options.limit;
    this.page_count = options.page_count;
    this.details_url = options.details_url;
    this.add_new = options.add_new;
    this.page = 1;
    this.filters = {};
    this.order = [['id', 'desc']];
}


CRUDList.prototype = new CRUD();


CRUDList.prototype.render = function()
{
    CRUD.prototype.render.call(this);
    this.updateRows();
    this.setPaginator();
    this.setupEvents();
}


CRUDList.prototype.setupEvents = function()
{
    var crudlist = this;

    // limit
    this.container.find('.limit form').submit(function(e) {
        e.preventDefault();
        crudlist.limit = $(this).find('input[name="limit"]').val();
        crudlist.page = 1;

        if (CRUD.settings.debug) {
            console.log('Changed limit!');
            console.log('New Limit: ' + crudlist.limit);
            console.log('New Page: ' + crudlist.page);
        }

        crudlist.updateRows(function() { crudlist.setPaginator(); });
    });

    // add filter
    this.container.find('.add_filter').submit(function(e) {
        e.preventDefault();

        var by_elem = $(this).find('select.by');
        var by = by_elem.val();

        var op_elem = $(this).find('select.op');
        var op = op_elem.val();

        var val_elem = $(this).find('*[name="' + by + '"]');
        var val = val_elem.val();

        // reset widget
        by_elem.find('option').first().attr('selected', 'selected');
        op_elem.find('option').first().attr('selected', 'selected');
        val_elem.val('');

        if (by != "0") {
            crudlist.filters[by] = [op, val];
        }

        if (CRUD.settings.debug) {
            console.log('Added filter!');
            console.log('Filters: ');
            console.log(crudlist.filters);
        }

        crudlist.updateFiltersTable();
        crudlist.updateRows();
        crudlist.setPaginator();
    });


    // change order
    this.container.find('.rows thead a').click(function(e) {
        e.preventDefault();

        var new_order = $(this).attr('href').substr(1);

        if (crudlist.order[0][0] == new_order) {
            if (crudlist.order[0][1] == 'asc') {
                crudlist.order[0][1] = 'desc';
            } else {
                crudlist.order[0][1] = 'asc';
            }
        } else {
            crudlist.order[0][0] = new_order;
            crudlist.order[0][1] = 'asc';
        }

        if (CRUD.settings.debug) {
            console.log('Changed order!');
            console.log('New order: ');
            console.log(crudlist.order);
        }

        crudlist.updateRows();
    });

    // add row
    this.container.find('.create form').submit(function(e) {
        e.preventDefault();

        var form = this;

        CRUD.ajax_load_img.show();

        $.ajax({
            type: "POST",
            url: CRUD.settings.put_url,
            data: {
                hash:  crudlist.hash,
                props: CRUD.fixMultipleCheckbox($(this).serializeArray())
            },
            dataType: "json",
            success: function(id) {
                var has_file = $(form).find('input[type="file"]').size();

                if (has_file) {
                    CRUD.upload(
                        form,
                        id,
                        crudlist.hash,
                        function(success, message) {
                            if (success) {
                                crudlist.updateRows();
                                CRUD.resetForm($(form));
                                alert('Done! ' + message);
                            } else {
                                crudlist.deleteRow(id);
                                alert('Error while uploading! ' + message);
                            }
                        }
                    );
                } else {
                    crudlist.updateRows();
                    CRUD.resetForm($(form));
                    alert('Done!');
                }

                CRUD.ajax_load_img.hide();
                $.fancybox.close();
            },
            error: function(jqxhr, status, error) {
                CRUD.ajax_load_img.hide();
                $.fancybox.close();
                alert('Create error!');
                if (CRUD.settings.debug) {
                    console.log(jqxhr);
                    console.log(status);
                    console.log(error);
                }
            }
        });
    });
}


CRUDList.prototype.updateFiltersTable = function()
{
    var crudlist = this;
    var container = this.container.find('.filters tbody');

    container.empty().append(
        Handlebars.templates.filter_row({filters: this.filters})
    );

    container.find('input[type="button"]').click(function() {
        delete crudlist.filters[$(this).val()];
        $(this).parent().parent().remove();
        crudlist.updateRows();
    });
}


CRUDList.prototype.updateRows = function(postUpdate)
{
    var crudlist = this;

    $.ajax({
        type: "GET",
        url: CRUD.settings.get_url,
        data: {
            hash:    this.hash,
            order:   this.order,
            filters: this.filters,
            limit:   this.limit,
            page:    this.page
        },
        dataType: "json",
        cache: false,
        success: function(data) {
            crudlist.setRows(data.rows);

            crudlist.page_count = data.page_count;

            if (postUpdate) { postUpdate(); }
        },
        error: function(jqxhr, status, error) {
            alert('Read error!');
            if (CRUD.settings.debug) {
                console.log(jqxhr.responseText);
                console.log(status);
                console.log(error);
            }
        }
    });
}


CRUDList.prototype.setRows = function(rows)
{
    var crudlist = this;
    var container = this.container.find('.rows tbody').empty();

    for (i in rows) for (j in rows[i]) {
        var r;

        if (rows[i][j] instanceof Array) {
            rows[i][j] = rows[i][j].join(', ');
        }
    }
    
    container.append(
        Handlebars.templates.data_row(
            {rows: rows, details_url: this.details_url, add_new: this.add_new}
        )
    );

    container.find('input.delete').click(function() {
        if (!confirm("Are you sure?")) { return; }
        crudlist.deleteRow($(this).val(), $(this).parent().parent());
    });
}


CRUDList.prototype.deleteRow = function(id, row_elem)
{
    $.ajax({
        type: "GET",
        url: CRUD.settings.delete_url,
        data: {
            hash: this.hash,
            id:   id,
        },
        dataType: "json",
        cache: false,
        success: function(data) {
            if (row_elem) {
                row_elem.fadeOut();
            }
        },
        error: function(jqxhr, status, error) {
            alert('Delete error!');
            if (CRUD.settings.debug) {
                console.log(status);
                console.log(error);
            }
        }
    });
}


CRUDList.prototype.setPaginator = function()
{
    var paginator = this.container.find('.paginator .body');

    paginator.empty().append(
        Handlebars.templates.paginator_row({page_count: this.page_count})
    );

    var crudlist = this;

    paginator.find('a').click(function(e) {
        e.preventDefault();
        crudlist.page = $(this).attr('href').substr(1);
        crudlist.updateRows();

        $(this).parent().find('a').removeClass('current');
        $(this).addClass('current');
    });

    paginator.find('a').first().addClass('current');
}


function CRUDDetail(container, options)
{
    CRUD.call(this, container, options);

    this.hash = options.main.hash;
    this.id = options.main.id;
}


CRUDDetail.prototype = new CRUD();


CRUDDetail.prototype.render = function()
{
    CRUD.prototype.render.call(this);
    this.setupEvents();
}


CRUDDetail.prototype.setupEvents = function()
{
    var cruddetail = this;

    this.container.find('.update form').submit(function(e) {
        e.preventDefault();

        CRUD.ajax_load_img.show();

        $.ajax({
            type: "POST",
            url: CRUD.settings.post_url,
            data: {
                hash:   cruddetail.hash,
                id:     cruddetail.id,
                props:  CRUD.fixMultipleCheckbox($(this).serializeArray())
            },
            dataType: "json",
            success: function(data) {
                alert('Done!');
                CRUD.ajax_load_img.hide();
            },
            error: function(jqxhr, status, error) {
                CRUD.ajax_load_img.hide();
                alert('Update error!');
                if (CRUD.settings.debug) {;
                    console.log(status);
                    console.log(error);
                }
            }
        });
    });
}


// This assumes the form has file fields
// Currently only one file per form, it is named the id of the record
// Currently only for creating, update not supported
// Check out AjaxCtrl::uploadAct()
CRUD.upload = function(form, id, crud_hash, callback)
{
    var CALLBACK_NAME = 'crud_file_upload_callback';

    window[CALLBACK_NAME] = callback;

    try {
        var tmp_iframe
            = $('<iframe id="tmp_iframe" name="tmp_iframe"></iframe>')
                .appendTo($('body'))
                .hide();

        var tmp_form
            = $('<form />')
                .insertBefore($(form))
                .attr('enctype', 'multipart/form-data')
                .attr('target', 'tmp_iframe')
                .attr('method', 'post')
                .attr('action', CRUD.settings.upload_url)
                .append('<div class="helper" />')
                .append('<div id="uploading_txt">Uploading...</div>')
                .append('<input type="hidden" name="id" value="' + id + '" />')
                .append('<input type="hidden" name="callback_name" value="' + CALLBACK_NAME + '" />')
                .append('<input type="hidden" name="hash" value="' + crud_hash + '" />');


        tmp_form.find('.helper').append($(form).children());
        tmp_form.submit();

        $(form).append(tmp_form.find('.helper').children());
        tmp_form.remove();
    } catch (e) {
        callback(0, 'Frontend error: ' + e);
    }
}


CRUD.resetForm = function(form)
{
    form.find('input:text, input:password, input:file, select, textarea')
        .val('');

    form.find('input:radio, input:checkbox')
        .removeAttr('checked').removeAttr('selected');
}


CRUD.fixMultipleCheckbox = function(arr)
{
    var name2index = {}; // helper
    var new_arr = [];

    for (var i=0, j=arr.length; i<j; ++i) {
        var name = arr[i]['name'].trimChar('\\[\\]');

        if (!(name in name2index)) {
            name2index[name] = i;
            new_arr.push({name: name, value: arr[i].value});
            continue;
        }

        var target = new_arr[name2index[name]];

        if (typeof target.value != 'object') {
            target.value = [target.value];
        }

        target.value.push(arr[i]['value']);
    }

    return new_arr;
}


CRUD.setupInterface = function()
{
    // tabs
    $('.crud .relations.tabs').tabs();

    // date fields
    $('.crud input.date').datepicker({
        dateFormat: "dd.mm.yy",
        showButtonPanel: true
    });

    // confirm delete
    $('.crud .rows input.delete').click(function(e) {
        if (!confirm('Emin misiniz?') || !confirm('Son kararınız mı?')) {
            e.preventDefault();
        }
    });
    
    // add filter
    $('.crud .add_filter select.by').change(function() {
        $('.crud .add_filter .widgets>*')
            .hide()
            .filter('*[name="' + $(this).val() + '"]')
            .show();
    });

    $('.crud .add_filter select.by').change();

    // new item popup
    $('a.popup').click(function() {
        $.fancybox.open($(this).next().show());
    });

    // setup ajax loader image
    CRUD.ajax_load_img =
        $("<img src='" + CRUD.settings.path + "/ajax_loader.gif' />")
            .appendTo("body")
            .css({
                position: "absolute",
                left    : "47%",
                top     : "400px",
                zIndex  : "10000",
                width   : "100px"
            })
            .hide();
}


CRUD.setupHandlebars = function()
{
    Handlebars.partials = Handlebars.templates;

    Handlebars.registerHelper(
        'eachWidget',
        /**
         * eachWidget helper
         * @param mode
         *   0: hide readonly fields
         *   1: show readonly fields in readonly widgets
         *   2: show readonly fields normally
         */
        function(widgets, mode, options) {
            var out = "", data;

            for (var i = 0; i < widgets.length; i++) {
                if (!mode && !widgets[i].write)
                {
                    continue;
                } else if (mode == 1) {
                    widgets[i].readonly_active = true;
                }

                if (options.data) {
                    data = Handlebars.createFrame(options.data || {});

                    data.widget = new Handlebars.SafeString(
                        Handlebars.partials["w" + widgets[i]["type"]](widgets[i])
                    );
                }

                out += options.fn(widgets[i], { data: data });
            }

            return out;
        }
    );

    Handlebars.registerHelper('times', function(i, options) {
        var out = "";

        for (var j = 1; j <= i; ++j) {
            out += options.fn(null, {data: {index: j}});
        }

        return out;
    });
}


CRUD.setupHelpers = function()
{
    /**
     * input goes directly into regexp character class brackets
     * and thus should be encoded accordingly.
     */
    String.prototype.trimChar = function(chr_cls) {
        var re_str = '^[' + chr_cls + ']+|[' + chr_cls + ']+$';
        return this.replace(new RegExp(re_str, 'g'), '');
    };
}
