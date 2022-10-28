<?php

class User extends AppModel
{
    protected $tableName = 'member';

    public static function model($className = __CLASS__)
    {
        return new $className();
    }
}
