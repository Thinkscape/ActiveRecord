<?php
namespace Thinkscape\ActiveRecord\Persistence;

interface PersistenceInterface
{
    /**
     * Store instance data in the database
     *
     * @return void
     */
    function save();

    /**
     * Load instance data from the database
     *
     * @return void
     */
    function load();

    /**
     * Reload data from the database
     * @return void
     */
    function reload();

    /**
     * Delete the instance presence from the database
     *
     * @return mixed
     */
    function delete();

    /**
     * Retrieve model field names from the database
     *
     * @return mixed
     */
    static function getFieldsFromDatabase();
}