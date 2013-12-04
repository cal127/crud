<?php

namespace CRUD;


class REST
{
    public static function create($params)
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

        return json_encode($id);
    }


    public static function read()
    {
        $crud = $_SESSION['CRUDPage']['crud'][$_POST['hash']];

        $crud->limit($_POST['limit'], $_POST['page']);

        $crud->resetFilters();

        if (isset($_POST['filters'])) {
            $crud->filter($_POST['filters']);
        }

        if (isset($_POST['order'])) {
            $crud->order($_POST['order']);
        }

        $rows = $crud->getRows();

        $page_count = $crud->getPageCount();

        return json_encode(compact('rows', 'page_count'));
    }


    public static function update($params)
    {
        $props = array_combine(
            array_map(function($x) { return $x['name']; }, $_POST['props']),
            array_map(function($x) { return $x['value']; }, $_POST['props'])
        );

        $crud = $_SESSION['CRUDPage']['crud'][$_POST['hash']];
        $id = $crud->update($props, $_POST['id']);

        return json_encode($id);
    }


    public static function delete($params)
    {
        $crud = $_SESSION['CRUDPage']['crud'][$_POST['hash']];
        $crud->delete($_POST['id']);

        return null;
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

        return sprintf(
            '<script type="text/javascript">window.top.window.%s(%s, "%s");'
                . '</script>',
            $_POST['callback_name'],
            $success,
            $message
        );
    }
}
