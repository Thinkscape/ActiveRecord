<?php
namespace Thinkscape\ActiveRecord\Persistence;

interface PersistenceInterface
{
    /**
     * Store instance data in the database
     *
     * @return void
     */
    public function save();

    /**
     * Load instance data from the database
     *
     * @return void
     */
    public function load();

    /**
     * Reload data from the database
     * @return void
     */
    public function reload();

    /**
     * Delete the instance presence from the database
     *
     * @return mixed
     */
    public function delete();

    /**
     * Retrieve model field names from the database
     *
     * @return mixed
     */
    public static function getFieldsFromDatabase();
}
