<?php
/**
 * Created by PhpStorm.
 * User: ng
 * Date: 03/07/17
 * Time: 21:30
 */

namespace Siworks\Slim\Doctrine\Model;


interface IModel
{
    public function create( Array $data );

    public function update($args, Array $data );

    public function findOne( $args );

    public function findAll( Array $data );

    /**
     * Hydrate object from array data;
     *
     * @param Object $obj
     * @param Array $data
     *
     * @return Entity
     */
    function populateObject($obj);
}
