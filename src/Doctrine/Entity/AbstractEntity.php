<?php
/**
 * Example Project (http://www.siworks.com)
 *
 * Created by Rafael N. Garbinatto (rafael@siworks.com)
 * date 25/03/2017
 *
 * @copyright Siworks
 */
namespace Siworks\Slim\Doctrine\Entity;

use Siworks\Slim\Doctrine\Traits\Entity\TimeStampable;
use Siworks\Slim\Doctrine\Traits\Helpers\ObjectHelpers;
use Doctrine\ORM\Mapping as ORM;
use Exchanger\Exception\Exception;

/**
 * Class AbstractEntity
 * @package Siworks\Slim\Doctrine\Entity
 *
 */
Abstract class AbstractEntity
{
    use TimeStampable,
        ObjectHelpers;

    public function __call($methodName, $params = null)
    {
        $action = substr($methodName, 0, 3);
        $field = lcfirst(substr($methodName, 3));

        if (property_exists($this, $field))
        {
            if ($action == 'set')
            {
                $this->$field = $params[0];
                return $this;
            }
            elseif($action == 'get')
            {
                return $this->$field;
            }
            throw new \RuntimeException('The method is not defined. (ABSENT-11001exc)', 11001);
        }
        throw new \InvalidArgumentException("Attribute {$field} does not exist. (ABSENT-11002exc)", 11002);
    }
}