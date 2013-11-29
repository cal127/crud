<?php

namespace CRUD;


use \Exception;


class CRUD
{
    /**
     * @var ORMWrapper $orm
     *
     * Paris ORMWrapper instance. This is our data source here.
     * Provided by user in constructor
     */
    private $orm;


    /*
     * @var array $filters
     *
     * Filters. Provided by user in $this->filter()
     * 
     * Structure:
     * [
     *   <field> => [<filter_type>, <filter_content>],
     *   ...
     * ]
     *
     * string <field>          : field name to filter by
     * string <filter_type>    : =|%|>|<
     * string <filter_content> : content to filter with
     */
    private $filters;


    /**
     * idiorm where conditions
     */
    private $base_where_conditions = array();


    /*
     * @var integer $limit
     *
     * Limit as you know it. Provided by user in $this->limit()
     */
    private $limit;
    

    /*
     * @var int $page
     *
     * Current page number. Provided by user in $this->limit()
     */
    private $page;


    /*
     * @var array $order
     *
     * Variable denoting order of data. Provided by user in $this->order()
     *
     * Structure:
     * [
     *   [<field>, <direction>],
     *   ...
     * ]
     *
     * string <field>     : Field name to order by
     * string <direction> : asc|desc
     */
    private $order;


    /*
     * @var array $callbacks
     *
     ***************************************************************************
     * GLOBAL CALLBACKS INCLUDED AS OF NOW under the key 'global'
     * These are injected in $callbacks when a new object gets instantiated.
     * Provided by user in self::addGlobalCallbacks()
     ***************************************************************************
     *
     * Callbacks for pre & post processing.
     *
     * Altered by both user and class. Users can add new callbacks
     * via constructor. And the class injects global callbacks, 
     * many to many callback, and virtual fields callback, which are
     * explained next:
     *
     * Global callbacks       : Documented in class variable $global_callbacks
     *
     * Many to many callback  : Note: This is an implementation detail.
     *                          Creates records in the intermediate table,
     *                          as the save() method is only capable of 
     *                          modifying the base table.
     *
     * File callback          : Note: This is an implementation detail.
     *                          The save() method, which saves the...
     *
     * Delete Virtual field callback : Note: This is an implementation detail.
     *                          Virtual fields are fields which can be
     *                          read & written in the CRUD app, but which
     *                          are not really fields in the corresponding
     *                          table. Examples are file and many to many
     *                          fields.
     *                          
     *                          This callback removes virtual fields from
     *                          the fields array before they are saved.
     *                          This is needed because the save() method
     *                          only saves field present in the table in
     *                          question, and throws error for non-existent
     *                          fields.
     *                          
     *                          Virtual are fields of type 'rel_many' and 'file' for now.
     *
     *
     * Structure:
     * [
     *   'pre_save' => [<callback>, ...],
     *   'post_save' => [<callback>, ...],
     *   'pre_read' => [<callback>, ...]
     * ]
     *
     * callable <callback>: Callables which are passed following parameters:
     *   pre_save  : <props>, <fields>
     *   post_save : <id>, <props>, <fields>
     *   pre_read  : <props>, <fields>
     *
     *  <id>     : id of the current row
     *  <props>  : data of the current row
     *  <fields> : fields meta variable
     *
     */
    private static $callbacks; 


    /**
     * Default values to use when saving
     */
    private $defaults = array();


    // VARS THAT ARE GENERATED AUTOMATICALLY


    /**
     * @var array $fields
     *
     * Fields we want to read & write. Used fetch data from db,
     * generate create & update forms, and generate titles for each.
     *
     * Structure:
     * [
     *   [<name>, <type>, <rel>, <mod>],
     *   ...
     * ]
     *
     * string <name> : Name of field
     * string <type> : text|rel|date|file
     * string <rel>  : Related model & field, as in <model>.<field>
     * int    <mod>  : 2|4|6
     *                 Mod of field is its read & write permissions
     *                 4 is read, 2 is write and 6 is read & write
     *                 read means the field is shown in the table,
     *                 and write means it is modifiable
     */
    private $fields;


    /**
     * @var string $model_name
     *
     * Class name of the Paris model. This is derived from $this->orm.
     * Short model name is the same class name but without auto prefix,
     * if it exists. Refer to Paris' Model::$auto_prefix_models variable
     * for auto prefix.
     */
    private $model_name;
    private $short_model_name;


    /**
     * @var int $page_count
     *
     * Total page count. Calculated in $this->limit()
     */
    private $page_count;


    /**
     * @var hash
     *
     * md5 checksum of the serialization of the first parameter of constructor,
     * as it is entered by the user
     */
    private $hash;


    /**
     * @var array $titles
     *
     * Titles for all readable fields.
     * Arranged for a possible select box type representation.
     *
     * Structure:
     * [
     *   [value: <value>, text: <text>],
     *   ...
     * ]
     *
     * string <value>: Field Name
     * string <text>: Title
     *
     */
    private $titles = array();


    /** 
     * Widgets. Needs thorough documentation
     */
    private $widgets = array();


    /**
     * File upload dir for file fields.
     */
    private static $upload_dir;


    /**
     * global date callbacks
     * two callbacks in an array, write & read, for conversion between db & ui
     */
    private static $global_date_callbacks = array();



    // GETTERS

    public function getOrmClone()       { return clone $this->orm; }
    public function getOrder()          { return $this->order; }
    public function getLimit()          { return $this->limit; }
    public function getFilters()        { return $this->filters; }
    public function getPage()           { return $this->page; }
    public function getFields()         { return $this->fields; }
    public function getModelName()      { return $this->model_name; }
    public function getShortModelName() { return $this->short_model_name; }
    public function getHash()           { return $this->hash; }

    public function getTitles()
    {
        if (count($this->titles)) { return $this->titles; }

        return $this->titles = array_values(array_map(
            function($f) {
                return array(
                    'value' => $f['name'],
                    'text'  => $f['title'],
                    'read'  => $f['read'],
                    'write' => $f['write']
                );
            },
            array_filter(
                $this->fields,
                function ($f) {
                    return
                        $f['type'] != 'file' && $f['type'] != 'file_multiple';
                }
            )
        ));
    }


    // SETTERS

    /**
     * @method addGlobalCallback
     *
     * Adds a global callback.
     * 
     * In order for the callable provided here to be
     * injected to an object, this function must be called before that
     * particular object's instantiation
     *
     * @param string $type       : pre_save|post_save|pre_read
     * @param callable $callback : Refer to <callback> element's
     *                             documentation in $callbacks instance
     *                             variable's documentation
     */
     /*
      * scope is expected to be a model name or an ormwrapper object
      * that's why is being hashed. '@global' token means global.
      */
    public static function addCallback($owner, $type, $callbacks)
    {
        if (!is_array($callbacks)) { $callbacks = array($callbacks); }

        if (is_array($owner)) {
            foreach ($owner as $o) {
                call_user_func(__METHOD__, $o, $type, $callbacks);
            }

            return;
        } elseif (!$owner) {
            $owner = '@global';
        } elseif (!is_string($owner)) {
            $owner = self::hash($owner);
        }

        foreach ($callbacks as $c) {
            self::$callbacks[$owner][$type][] = $c;
        }
    }


    /**
     *
     */
    public function setDefaults($props)
    {
        if (!is_array($props)) { $props = array($props); }
        $this->defaults = $props;
    }


    /**
     * File upload dir. Always ending with slash
     */
    public static function setUploadDir($upload_dir)
    {
        self::$upload_dir = rtrim('/', $upload_dir) . '/';
    }


    /**
     * set global date callbacks
     */
    public static function setGlobalDateCallbacks($global_date_callbacks)
    {
        self::$global_date_callbacks =  $global_date_callbacks;
    }


    // MISC
    public static function hash($foo)
    {
        return md5(serialize($foo));
    }


    public function __construct($orm, $fields)
    {
        $this->hash = self::hash($orm);

        $this->orm = is_string($orm) ? Model::factory($orm) : $orm;

        $this->calculateBaseFilter();
        $this->resetFilters();

        // build model names
        $this->model_name = $this->orm->getClassName();

        $this->short_model_name = ($len = strlen(Model::$auto_prefix_models))
                                 ? substr($this->model_name, $len)
                                 : $this->model_name;

        // take care of fields
        $this->fields = self::parseFields($fields, $this->model_name);
    }


    protected function calculateBaseFilter($add = null)
    {
        $ids = array_map(
            function($row) { return $row['id']; },
            $this->orm->find_array()
        );

        if ($add) {
            $ids = array_merge(array($add), $ids);
        }

        // for a sensible default
        if (!count($ids)) {
            $ids = array(NULL);
        }

        $this->base_where_conditions = array(
            array(
                sprintf(
                    '`id` IN (%s)',
                    implode(', ', array_fill(0, count($ids), '?'))
                ),
                $ids
            )
        );
    }


    public function addToBaseFilter($id)
    {
        $this->calculateBaseFilter($id);
    }


    public function resetFilters()
    {
        $this->orm->setWhereConditions($this->base_where_conditions);
    }


    protected static function parseFields($fields, $model_name)
    {
        $new_fields = array();

        foreach ($fields as $field) {
            if (is_array($field)) {
                $title = $field[0];
                $name = $field[1];
                $extra_filter = isset($field[2]) ? $field[2] : null;
            } else {
                $title = null;
                $name = $field;
            }

            // Determine virtual
            $virtual = false;

            if ($name[0] == '~') { // means virtual
                $virtual = true;
                $name = substr($name, 1);
            }

            // Determine mods
            $read = $write = true;

            if ($name[0] == '[') { // means if readonly
                $write = false;
                $name = substr($name, 1);
            } elseif ($name[0] == '+') { // means if writeonly
                $read = false;
                $name = substr($name, 1);
            }

            // Determine type
            $type_map = array(
                '*' => 'file',
                '#' => 'long',
                '!' => 'flag',
                '%' => 'file_multiple'
            );

            if (in_array($name[0], array_keys($type_map))) {
                $type = $type_map[$name[0]];
                $name = substr($name, 1);
            } elseif (strpos($name, '.') !== false) {
                $type = 'rel';
            } elseif (substr($name, -4) == 'date') {
                $type = 'date';    
            } else {
                $type = 'text';
            }


            // Determine relation details for relation fields
            if ($type == 'rel') {
                $rel = self::parseRelationDetails($name, $model_name);
            } else {
                $rel = null;
            }

            // Determine virtual vol 2
            if (
                   $type == 'file'
                || (
                           $type == 'rel'
                        && in_array(
                            $rel['type'],
                            array('has_one', 'has_many', 'has_many_through')
                        )
                   )
            ) {
                $virtual = true;
            }


            // Generate Title
            $title = !empty($title)
                   ? $title
                   : self::generateTitle($name, $type, $rel);


            // Overwrite name if this is a rel/belongs_to type of field
            if ($type == 'rel' && $rel['type'] == 'belongs_to') {
                $name = $rel['key1'];
            }


            // Finish
            $new_fields[$name] = compact(
                'name',
                'title',
                'type',
                'rel',
                'read',
                'write',
                'virtual',
                'extra_filter'
            );
        }

        return $new_fields;
    }


    /**
     * This must be called after relations of fields are defined
     */
    public static function generateTitle($name, $type, $rel)
    {
        if (
               $type == 'rel'
            && in_array($rel['type'],
                        array('has_many', 'has_many_through'))
        ) {
            list($name) = explode('.', $name);
        }

        $title = rtrim(ucwords(str_replace(array('_', '.'), ' ', $name)), '[]');

        return $title;
    }


    public static function parseRelationDetails($field, $model_name)
    {
        // parse the body of the relator method and
        // get the relation type, related model name,
        // intermediate model name (if many to many),
        // and custom key field names (if any)
        list($relator_mtd, $related_model_field) = explode('.', $field);

        list($relator_mtd_body, $relator_mtd_doccomment)
            = Helpers::getMethodBodyAndDocComment(
                $model_name, // full model class name
                $relator_mtd
            );

        // parse doc comment and get @filter helper, if any
        if (
            preg_match(
                '/@filter ([\w=&,]+)/',
                $relator_mtd_doccomment,
                $matches
            )
        ) {
            $filters = explode('&', $matches[1]);
        } else {
            $filters = array();
        }

        // parse the function body and get relation_type,
        // related model name, intermediate table name,
        // and keys, if any.

        $match = preg_match(
            '/return \$this->([\w_]+)\\(([\'"\w,\s]+)/',
            $relator_mtd_body,
            $matches
        );

        list($full_match, $rel_type, $args) = $matches;

        $args = array_map(
            function($x) { return trim($x, ' \''); },
            explode(',', $args)
        );

        $related_model_name = array_shift($args);
        $related_full_model_name
            = Model::$auto_prefix_models . $related_model_name;

        list($inter_model_name, $key1, $key2) = array(null, null, null);

        switch ($rel_type) {
            case 'belongs_to':
                list($key1) = array_merge($args, array(null));
                $key1 = Model::buildForeignKeyName(
                    $key1,
                    $related_full_model_name
                );
                break;

            case 'has_one':
            case 'has_many':
                list($key1) = array_merge($args, array(null));
                $key1 = Model::buildForeignKeyName($key1, $model_name);
                break;

            case 'has_many_through':
                list($inter_model_name, $key1, $key2)
                    = array_merge($args, array(null, null, null));

                $inter_model_name = Model::buildJoinClassName(
                    $inter_model_name,
                    $model_name,
                    $related_full_model_name
                );

                $key1 = Model::buildForeignKeyName($key1, $model_name);
                $key2 = Model::buildForeignKeyName($key2, $related_model_name);

                break;
        }

        return array(
            'relator_mtd'         => $relator_mtd,
            'related_model_field' => $related_model_field,
            'type'                => $rel_type,
            'related_model_name'  => $related_model_name,
            'inter_model_name'    => $inter_model_name,
            'key1'                => $key1,
            'key2'                => $key2,
            'filters'             => $filters
        );
    }


    private function getCallbacks()
    {
        if (!isset(self::$callbacks['@global'])) {
            self::initializeCallbacks();
        }

        return Helpers::arrayMergeByKey(
            self::$callbacks['@global'],
            isset(self::$callbacks[$this->getShortModelName()])
                ? self::$callbacks[$this->getShortModelName()]
                : array(),
            isset(self::$callbacks[$this->hash])
                ? self::$callbacks[$this->hash]
                : array()
        );
    }


    private static function initializeCallbacks()
    {
        // Date callbacks //////////////////////////////////////////////////////
        if (count(self::$global_date_callbacks)) {
            $read_callback = self::$global_date_callbacks[0];

            self::addCallback(
                null,
                'pre_read',
                function($props, $fields) use ($read_callback) {
                    foreach ($props as $prop_name => &$prop_value) {
                        if ($fields[$prop_name]['type'] != 'date') { continue; }

                        $prop_value = call_user_func(
                            $read_callback, $prop_value
                        );
                    }

                    return $props;
                }
            );

            $write_callback = self::$global_date_callbacks[1];

            self::addCallback(
                null,
                'pre_save',
                function($props, $fields) use ($write_callback) {
                    foreach ($props as $prop_name => &$prop_value) {
                        if (
                               $fields[$prop_name]['type'] != 'date'
                            || !$fields[$prop_name]['write']
                        ) {
                            continue;
                        }

                        $prop_value = call_user_func(
                            $write_callback, $prop_value
                        );
                    }

                    return $props;
                }
            );
        }

        // File callback ///////////////////////////////////////////////////////
        self::addCallback(
            null,
            'post_save',
            function($obj, $props, $fields) {
                foreach ($props as $prop_name => $prop_value) {
                    if ($fields[$prop_name]['type'] != 'file') { continue; }

                    $ext = pathinfo(
                        $_FILES['props']['name']['file'],
                        PATHINFO_EXTENSION
                    );

                    move_uploaded_file(
                        $_FILES['props']['tmp_name']['file'],
                        self::$upload_dir . $obj->id . $ext
                    );
                }
            }
        );

        // Has-one callback ////////////////////////////////////////////////////
        self::addCallback(
            null,
            'post_save',
            function($obj, $props, $fields) {
                foreach ($props as $prop_name => $prop_value) {
                    if (
                           $fields[$prop_name]['type'] != 'rel'
                        || $fields[$prop_name]['rel']['type'] != 'has_one'
                        || !$fields[$prop_name]['write']
                    ) { continue; }

                    $related_model
                        = $fields[$prop_name]['rel']['related_model_name'];

                    $related_obj = Model::factory($related_model)
                      ->where('id', $prop_value)
                      ->find_one();

                    $related_obj->{$fields[$prop_name]['rel']['key1']}
                        = $obj->id;
                    $related_obj->save();
                }
            }
        );

        // Has-many-through callback ///////////////////////////////////////////
        self::addCallback(
            null,
            'post_save',
            function($obj, $props, $fields) {
                foreach ($props as $prop_name => $prop_value) {
                    if (
                           $fields[$prop_name]['type'] != 'rel'
                        || $fields[$prop_name]['rel']['type'] != 'has_many_through'
                        || !$fields[$prop_name]['write']
                    ) { continue; }

                    $inter_model = $fields[$prop_name]['rel']['inter_model_name'];

                    Model::factory($inter_model)
                      ->where($fields[$prop_name]['rel']['key1'], $obj->id)
                      ->delete_many();

                    if (!is_array($prop_value)) {
                        $prop_value = array($prop_value);
                    }

                    foreach ($prop_value as $rel_obj_id) {
                        $inter = Model::factory($inter_model)->create();
                        $inter->{$fields[$prop_name]['rel']['key1']} = $obj->id;
                        $inter->{$fields[$prop_name]['rel']['key2']} = $rel_obj_id;
                        $inter->save();
                    }
                }
            }
        );

        // remove-virtual-fields callback //////////////////////////////////////
        self::addCallback(
            null,
            'pre_save',
            function($props, $fields) {
                $filtered_props = array();

                foreach ($props as $prop_name => $prop_value) {
                    if (!$fields[$prop_name]['virtual']) {
                        $filtered_props[$prop_name] = $prop_value;
                    }
                }

                return $filtered_props;
            }
        );
    }


    public function filter($filters)
    {
        $filter_map = array(
            '=' => 'where_equal',
            '>' => 'where_gt',
            '<' => 'where_lt',
            '~' => 'where_like'
        );

        $this->resetFilters();

        foreach ($filters as $f_name => $f_details) {
            if ($f_details[0] == '~') {
                $f_details[1] = '%' . $f_details[1] . '%';
            }

            $this->orm = $this->orm
                ->$filter_map[$f_details[0]]($f_name, $f_details[1]);
        }

        $this->filters = $filters;

        return $this;
    }


    public function order($order)
    {
        $this->orm->resetOrder();

        foreach ($order as $o) {
            $this->orm = $this->orm->{'order_by_' . $o[1]}($o[0]);
        }

        $this->order = $order;

        return $this;
    }


    public function limit($limit, $page = 1)
    {
        $offset = ($page - 1) * $limit; 

        // calculate page count ////////////////////////////////////////////////
        $copy = clone $this->orm;

        try {
            $this->page_count = ceil($copy->count() / $limit);

            if (!$this->page_count) { throw new Exception(); }
        } catch (Exception $e) {
            $this->page_count = 1;
        }

        ////////////////////////////////////////////////////////////////////////

        $this->orm = $this->orm
            ->limit($limit)
            ->offset($offset);

        $this->limit = $limit;
        $this->page = $page;

        return $this;
    }


    public function getPageCount()
    {
        if (empty($this->page_count)) {
            throw new Exception(__CLASS__ . '::limit() needs to be called '
                . ' in order to calculate page count.');
        }

        return $this->page_count;
    }


    /**
     * @arg bool raw
     * if raw is true, relational fields are returned their ids.
     */
    public function getRows($raw = false)
    {
        $rows = array();

        foreach ($this->orm->find_many() as $row) {
            foreach ($this->fields as $field) {
                // skip the only non-readable field type:
                if (
                       in_array($field['type'], array('file', 'file_multiple'))
                    || !$field['read']
                ) {
                    continue;
                }

                if ($field['type'] == 'flag') {
                    $map = array('No', 'Yes');

                    $rows[$row->id][$field['name']]
                        = $raw ? ($row->$field['name'] == '0' ? false : true)
                               : $map[$row->$field['name']];

                    continue;
                }
                
                if ($field['type'] != 'rel') {
                    $rows[$row->id][$field['name']] = (string) $row->$field['name'];
                    continue;
                } 

                switch ($field['rel']['type']) {
                    case 'belongs_to':
                    case 'has_one':
                        if ($raw) {
                            $val = $field['rel']['type'] == 'belongs_to'
                                 ? $row->{$field['rel']['key1']}
                                 : $row
                                    ->{$field['rel']['relator_mtd']}()
                                    ->id;

                            break;
                        }

                        try {
                          $val
                            = $row
                              ->{$field['rel']['relator_mtd']}()
                              ->{$field['rel']['related_model_field']};
                        } catch (Exception $e) {
                            $val = null;
                        }

                        break;


                    case 'has_many':
                    case 'has_many_through':
                        try {
                            $all_related_values
                              = $row
                                ->{$field['rel']['relator_mtd']}()
                                ->find_array();
                        } catch (Exception $e) {
                            $val = null;
                            break;
                        }

                        if ($raw) {
                            $related_model_field = 'id';
                        } else {
                            $related_model_field
                                = $field['rel']['related_model_field'];
                        }

                        $val = array_map(
                            function($row) use ($related_model_field) {
                                return $row[$related_model_field];
                            },
                            $all_related_values
                        );

                        break;
                }

                $rows[$row->id][$field['name']] = $val;
            }

            $rows[$row->id] = self::applyCallbacks(
                'pre_read', 1, $rows[$row->id], $this->fields
            );
        }

        return $rows;
    }


    public function getSingleRowWithTitles()
    {
        $rows = $this->getRows();
        $row = array_shift($rows);

        $fields = $this->fields;

        $titles = array_map(
            function($field_name) use ($fields) { return $fields[$field_name]['title']; },
            array_keys($row)
        );

        return array_combine($titles, $row);
    }


    public function getSingleRawRow()
    {
        $rows = $this->getRows(true);

        return array_shift($rows);
    }


    public function getWidgets($values = array())
    {
        if ($this->widgets) { return $this->widgets; }

        return $this->widgets = array_values(array_filter(array_map(
            function($field) use ($values) {
                $widget = array(
                    'type'     => $field['type'],
                    'title'    => $field['title'],
                    'name'     => $field['name'],
                    'read'     => $field['read'],
                    'write'    => $field['write']
                );

                if (isset($values[$field['name']])) {
                    $widget['value'] = $values[$field['name']];
                }

                if ($field['type'] != 'rel') { return $widget; }


                switch ($field['rel']['type']) {
                    case 'has_one':
                    case 'has_many':
                        return null;

                    case 'belongs_to':
                    case 'has_many_through':
                        $widget['type'] = $field['rel']['type']== 'belongs_to'
                                        ? 'choice'
                                        : 'multiple';

                        // Get all possible items //////////////////////////////
                        $orm = Model::factory(
                            $field['rel']['related_model_name']
                        );

                        // apply filters
                        foreach ($field['rel']['filters'] as $filter) {
                            list($filter_name, $filter_arg) = explode('=', $filter);
                            $filter_arg = explode(',', $filter_arg);
                            $orm = $orm->filter($filter_name, $filter_arg);
                        }

                        if (isset($field['extra_filter'])) {
                            $orm = $orm->filter(
                                $field['extra_filter'][0],
                                $field['extra_filter'][1]
                            );
                        }

                        try {
                            $orm
                                ->select_many(
                                    array('value' => 'id'),
                                    array(
                                      'text' => $field['rel']['related_model_field']
                                    )
                                );

                            if (!isset($values[$field['name']])) {
                                $items = $orm->find_array();
                            } else {
                                $val = $values[$field['name']]; // alias

                                $cond = $field['rel']['type'] == 'belongs_to'
                                      ? 'id = ' .  $val
                                      : 'id IN (' . implode(', ', $val) . ')';

                                $orm->select_expr($cond, 'active');

                                $items = $orm->find_array();

                                // ugly fix
                                $items = array_map(
                                    function($i) { $i['active'] = (bool)$i['active']; return $i; },
                                    $items
                                );
                            }
                        } catch (Exception $e) {
                            $items = array();
                        }
                        ////////////////////////////////////////////////////////

                        
                        $widget['choices'] = $items;

                        return $widget;
                }
            },
            $this->fields
        )));
    }


    public function create($props)
    {
        return $this->create_update($props);
    }


    public function update($props, $id)
    {
        return $this->create_update($props, $id);
    }


    /**
     * if id exists it means this is an update
     */
    private function create_update($props, $id = null)
    {
        $op = $id ? 'update' : 'create';

        $processed_props = $this->applyCallbacks(
            array('pre_save', 'pre_' . $op), 1, $props, $this->fields
        );

        $orm = Model::factory($this->short_model_name);
        $item = $id ? $orm->find_one($id) : $orm->create();
        $item->set(array_merge($this->defaults, $processed_props));
        $item->save();

        $this->applyCallbacks(
            array('post_save', 'post_' . $op), 0, $item, $props, $this->fields
        );

        return $item->id;
    }


    public function delete($id)
    {
        Model::factory($this->short_model_name)->find_one($id)->delete();

        $this->applyCallbacks('post_delete', 0, $id);
    }


    public function upload($post, $files, $id)
    {
        $this->applyCallbacks('upload', 0, $post, $files, $id);
    }


    private function applyCallbacks($callback_types, $chain = false)
    {
        if (!is_array($callback_types)) {
            $callback_types = array($callback_types);
        }

        $callback_args = array_slice(func_get_args(), 2);

        if ($chain) {
            $result = array_shift($callback_args);
        }

        try {
            $callbacks = $this->getCallbacks();

            foreach ($callback_types as $ct) {
                if (!isset($callbacks[$ct])) { continue; }

                foreach ($callbacks[$ct] as $f) {
                    if (is_callable($f)) {
                        if ($chain) {
                            $result = call_user_func_array(
                                $f,
                                array_merge(array($result), $callback_args)
                            );
                        } else {
                            call_user_func_array($f, $callback_args);
                        }
                    }
                }
            }
        } catch (Exception $e) { throw $e; }

        if ($chain) { return $result; }
    }
}
