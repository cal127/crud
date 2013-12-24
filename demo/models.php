<?php

use \CRUD\Model;


class Cls extends Model
{
    public function teacher()
    {
        return $this->belongs_to('Teacher')->find_one();
    }

    public function students()
    {
        return $this->has_many('Student');
    }
}

class Teacher extends Model
{
    public function classes()
    {
        return $this->has_many('Cls');
    }
}

class Student extends Model
{
    public function cls()
    {
        return $this->belongs_to('Cls')->find_one();
    }
}
