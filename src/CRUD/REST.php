<?php

namespace CRUD;


class REST
{
    public static function get($params)
    {
        parse_str(substr($params, 1), $values);

        $crud = $_SESSION['CRUDPage']['crud'][$values['hash']];

        $crud->limit($values['limit'], $values['page']);

        $crud->resetFilters();

        if (isset($values['filters'])) {
            $crud->filter($values['filters']);
        }

        if (isset($values['order'])) {
            $crud->order($values['order']);
        }

        $rows = $crud->getRows();

        $page_count = $crud->getPageCount();

        print json_encode(compact('rows', 'page_count'));
    }


    public static function post($params)
    {
        $props = array_combine(
            array_map(function($x) { return $x['name']; }, $_POST['props']),
            array_map(function($x) { return $x['value']; }, $_POST['props'])
        );

        $crud = $_SESSION['CRUDPage']['crud'][$_POST['hash']];
        $id = $crud->update($props, $_POST['id']);

        print json_encode($id);
    }


    public static function put($params)
    {
        if (!isset($_POST['props']) || !count($_POST['props'])) {
            $props = array();
        } else {
            $props = array_combine(
                array_map(function($x) { return $x['name']; }, $_POST['props']),
                array_map(function($x) { return $x['value']; }, $_POST['props'])
            );
        }

        $crud = $_SESSION['CRUDPage']['crud'][$_POST['hash']];
        $id = $crud->create($props);

        $crud->addToBaseFilter($id);
        $crud->resetFilters();

        print json_encode($id);
    }


    public static function delete($params)
    {
        parse_str(substr($params, 1), $values);

        $crud = $_SESSION['CRUDPage']['crud'][$values['hash']];
        $crud->delete($values['id']);
    }


    public static function upload($params)
    {
        $crud = $_SESSION['CRUDPage']['crud'][$_POST['hash']];

        try {
            $crud->upload($_POST, $_FILES, $_POST['id']);
            $success = 1;
            $message = '';
        } catch (Exception $e) {
            $success = 0;
            $message = 'Backend error: ' . $e->getMessage();
        }

        printf(
            '<script type="text/javascript">window.top.window.%s(%s, "%s");'
                . '</script>',
            $_POST['callback_name'],
            $success,
            $message
        );
    }
}
