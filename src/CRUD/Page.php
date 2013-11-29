<?php

namespace CRUD;


class Page
{
    public static function listall(
        $orm,
        $fields,
        $details_url = null,
        $add_new = true,
        $defaults = null,
        $limit = 10
    )
    {
        $crud = new CRUD($orm, $fields);
        $crud->limit($limit);
        if ($defaults) { $crud->setDefaults($defaults); }

        $_SESSION['CRUDPage']['crud'][$crud->getHash()] = $crud;

        return json_encode(array(
            'page_type'     => 'list',
            'hash'          => $crud->getHash(),
            'titles'        => $crud->getTitles(),
            'widgets'       => $crud->getWidgets(),
            'limit'         => $crud->getLimit(),
            'page_count'    => $crud->getPageCount(),
            'add_new'       => $add_new,
            'details_url'   => $details_url
        ));
    }


    public static function detail(
        $orm,
        $id,
        $fields,
        $writable = true,
        $relations = null,
        $relations_limit = 10
    )
    {
        // 'main' will refer to main object, and 'rel' to its relations
        $main_crud = new CRUD($orm, $fields);
        $main_crud->filter(array('id' => array('=', $id)));

        $_SESSION['CRUDPage']['crud'][$main_crud->getHash()] = $main_crud;

        $main = array(
            'hash'              => $main_crud->getHash(),
            'data'              => $main_crud->getSingleRowWithTitles(),
            'widgeted_data'     => $main_crud->getWidgets(
                                                 $main_crud->getSingleRawRow()
                                               ),
            'id'                => $id,
            'writable'          => $writable
        );

        // Relations
        $rels = array();

        if ($relations) {
            $main_model = $main_crud->getOrmClone()->find_one();
            $main_model_name = $main_crud->getModelName();

            foreach ($relations as $relator_mtd => $settings) {
                list($fields, $details_url, $add_new, $defaults, $limit)
                    = array_merge($settings, array(null, null, null, null));

                // set default values
                if (is_null($add_new)) { $add_new = true; }
                if (is_null($defaults)) { $defaults = array(); }


                $crud = new CRUD($main_model->$relator_mtd(), $fields);
                $crud->limit($relations_limit);

                // set defaults
                $rel_details = CRUD::parseRelationDetails($relator_mtd . '.', $main_model_name);
                $key_name = $rel_details['key1'];
                $crud->setDefaults(
                    array_merge($defaults, array($key_name => $id))
                );


                $_SESSION['CRUDPage']['crud'][$crud->getHash()] = $crud;

                $rels[$relator_mtd] = array(
                    'hash'          => $crud->getHash(),
                    'titles'        => $crud->getTitles(),
                    'widgets'       => $crud->getWidgets(),
                    'limit'         => $crud->getLimit(),
                    'page_count'    => $crud->getPageCount(),
                    'rel_title'     => ucwords(str_replace('_', ' ', $relator_mtd)),
                    'add_new'       => $add_new,
                    'details_url'   => $details_url
                );
            }
        }

        return json_encode(array(
            'page_type' => 'detail',
            'main'      => $main,
            'rels'      => $rels
        ));
    }
}
