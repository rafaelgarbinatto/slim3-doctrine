<?php
namespace Siworks\Slim\Doctrine\Repository;
/**
 * Created by PhpStorm.
 * User: ng
 * Date: 02/06/17
 * Time: 16:54
 */

use Doctrine\DBAL\Exception\InvalidArgumentException as InvalidArgumentException;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\ORMInvalidArgumentException as ORMInvalidArgumentException;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\ORM\EntityRepository as EntityRepository;
use Symfony\Component\Debug\Exception\ClassNotFoundException as ClassNotFoundException;
use Doctrine\ORM\Mapping;

Abstract class AbstractRepository extends EntityRepository
{

    /**
     * Get single entity object by Id
     *
     * @param Int $id
     *
     * @throws \\Doctrine\ORM\NoResultException
     *
     * @return Object | NULL
     */
    public function getSingleById($id)
    {
        try
        {
            $obj = $this->getEntityManager()->find($this->_entityName, $id);

            if ($obj instanceof $this->_entityName)
            {
                return $obj;
            }

            return NULL;
        }
        catch(\Doctrine\ORM\NoResultException $e)
        {
            throw $e;
        }
    }

    /**
     * Return list of records according to given start index and length
     *
     * @param Array $criteria specify where conditions
     * @param String $orderby specify columns, in which data should be ordered
     * @param Int $limit Determines how many records to fetch
     * @param Int $offset the start index number for the result entity list
     *
     * @return Array | NULL
     */
    function getSimpleListBy(Array $criteria = array(), $orderBy = NULL, $offset=0, $limit=10)
    {
        try
        {
            if ( ! $this->checkAttrib($criteria) )
            {
                $message = InvalidArgumentException::noColumnsSpecifiedForTable($this->getEntityName())->getMessage() . "(ABSREP-1001exc)";
                throw new \InvalidArgumentException($message,1001);
            }

            $res['data'] = $this->getEntityManager()->getRepository($this->_entityName)->findBy($criteria, $orderBy, $limit, $offset);
            $res['count'] = $this->getEntityManager()->getRepository($this->_entityName)->count($criteria);

            return $res;
        }
        catch(\Exception $e)
        {
            throw $e;
        }
    }

    /**
     * generic search to all repositories.
     *
     * @param string $status
     * @param int    $hydrate
     * @throws \\Doctrine\ORM\NoResultException
     * @throws \\Doctrine\ORM\NonUniqueResultException
     * @return \Paginator
     */
    public function getListBy(Array $filters, $offset=0, $limit=10, $hydrate = Query::HYDRATE_SINGLE_SCALAR)
    {
        try{

            if ( ! $this->checkAttrib([$this->_entityName, array_keys($filters)]) )
            {
                throw new \Doctrine\ORM\ORMInvalidArgumentException ("Invalid attribute filter (ABSREP-1002exc)",1002);
            }

            $qb = $this->createQueryBuilder('i');
            $qb->select('e')
                ->from($this->_entityName,$this->_entityName{0});

            if (isset($filters['where']))
            {
                $qb = $this->createWhere($filters['where'], $qb);
            }

            // fica para futuro
            /** if (isset($filters['COND'])) {
            $qb = parent::createConditional($filters['COND'], $qb);
            }**/

            if (isset($filters['orderBy']))
            {
                $qb = $this->createOrderBy($filters['orderBy'], $qb);
            }
            $query = $qb->getQuery();

            return $this->getPaginate($offset, $limit, $query);
        }
        catch (\Doctrine\ORM\NoResultException $e)
        {
            throw $e;
        }
        catch (\Exception $e)
        {
            throw $e;
        }
    }

    /**
     * check list attributes is exist in entity object
     *
     * @param array $list
     * @return bool
     */
    public function checkAttrib(array $list)
    {
        $list_filtred = $this->getAttributesList($list);
        if ( ! class_exists($this->_entityName) )
        {
            throw new EntityNotFoundException("Namespace {$this->_entityName} not found (ABSREP-1003exc)", 1003);
        }

        foreach ($list_filtred as $attrib => $values)
        {
            if ( ! property_exists($this->_entityName, $attrib) )
            {
                return FALSE;
            }
        }

        return TRUE;
    }

    public function getAttributesList(array $list)
    {
        $list = array_filter($list, function($value) {
            $arr = parent::createQueryBuilder('e')->getDQLParts();
            foreach ($arr as $keys){
                if ($keys == $value)
                {
                    return false;
                }
            }
            return $value;
        },ARRAY_FILTER_USE_BOTH);

        return $list;
    }

    /**
     * Create generics "where" on DQL with QueryBuilder
     *
     * @param array $arr
     * @param QueryBuilder $qb
     *
     * @return QueryBuilder
     */
    public function createWhere( Array $arr, QueryBuilder $qb)
    {

        foreach ($arr as $field => $value)
        {
            if ( ! $this->getClassMetadata()->hasField($field) )
            {
                continue;
            }

            $qb ->andWhere($qb->expr()->eq('e.'.$field, ':'.$field))
                ->setParameter($field, $value);
        }

        return $qb;
    }

    /**
     * Não está pronto parei aqui mas a ideia é boa =D, só não queria perder ela..where condicional generica
     *  ex.: Where (status = 1 AND status = 2 ) AND dataCreate = '0000-00-00'...
     *
     * @deprecated
     **/
    public function createConditional(Array $arr, $instr = null, QueryBuilder $qb)
    {
        if ( is_array($arr) )
        {
            foreach($arr as $field => $value)
            {
                if (is_array($value) && ! property_exists($this->getEntityName(), $field))
                {
                    $qb = self::createConditional($value, $field, $qb);
                }
                else{

                }
            }
        }
        else {
        }
    }

    /**
     * Create "Order by" in DQL instruction
     *
     * @param array $arr
     * @param $dql
     */
    public function createOrderBy(Array $arr, $dql){}

    /**
     * Create "Group by" in DQL instruction
     *
     * @param array $arr
     * @param $dql
     */
    public function createGroupBy(Array $arr, $dql){}

    /**
     * Get paged repository
     * @param Doctrine\ORM\Query query
     * @param int $offset
     * @param int $limit
     * @return Paginator
     */
    public function getPaginate(Doctrine\ORM\Query $query, $offset = 0, $limit = 10)
    {
        if( ! $query instanceof Query )
        {
            throw new ORMInvalidArgumentException ("Must be an instance of Doctrine\ORM\Query, instance of ".get_class($query)." given (ABSREP-1004exc)", 1004);
        }

        $paginator = new Paginator($query->getDQL());
        $paginator->getQuery()
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        return $paginator;
    }

    /**
     * @param Object $obj
     * @return Object
     * @throws \Exception
     */
    public function save($obj)
    {
        try{

            $this->getEntityManager()->persist($obj);
            $this->getEntityManager()->flush($obj);

            return $obj;
        }
        catch(\Doctrine\ORM\ORMInvalidArgumentException $e) {

            throw \Doctrine\ORM\ORMInvalidArgumentException ($e->getMessage() . "(ABSREP-1005exc)", 1005);
        }
        catch(\Exception $e) {
            throw $e;
        }
    }

    public function remove($obj)
    {
        try{
            $this->getEntityManager()->remove($obj);
            $this->getEntityManager()->flush($obj);

            return $obj;
        }
        catch(\Doctrine\ORM\ORMInvalidArgumentException $e)
        {
            throw \Doctrine\ORM\ORMInvalidArgumentException ($e->getMessage() . "(ABSREP-1006exc)", 1006);
        }
        catch(\Exception $e)
        {
            throw $e;
        }
    }
}