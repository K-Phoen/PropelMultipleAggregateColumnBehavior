<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

require_once 'MultipleAggregateColumnRelationBehavior.php';

/**
 * Keeps an aggregate column updated with related table
 *
 * @author     Nathan Jacobson
 * @author     Kévin Gomez
 */
class MultipleAggregateColumnBehavior extends Behavior
{
    /**
     * Parameter defaults for this behavior
     *
     * @var mixed
     */
    protected $parameters = array(
        'count' => null,
    );

    protected function getNbAggregates()
    {
        // no 'count' parameter given, assume there is one aggregate
        if ($this->getParameter('count') === null) {
            return 1;
        }

        return (int) $this->getParameter('count');
    }

    protected function getAggregateParameter($name, $aggregate)
    {
        if ($this->getParameter('count') === null) {
            return $this->getParameter($name);
        }

        return $this->getParameter($name.$aggregate);
    }


    /**
     * Modify the primary table - add aggregate column and aggregate column relation behavior
     */
    public function modifyTable() {
        // Loop through aggregates
        for ($x = 1; $x <= $this->getNbAggregates(); $x++) {

            $table = $this->getTable();
            if (!$columnName = $this->getAggregateParameter('name', $x)) {
                throw new InvalidArgumentException(sprintf('You must define a \'name$x\' parameter for the \'aggregate_column\' behavior in the \'%s\' table', $table->getName()));
            }

            // add the aggregate column if not present
            if (!$this->getTable()->containsColumn($columnName)) {
                $this->getTable()->addColumn(array(
                    'name'  => $columnName,
                    'type'  => 'INTEGER',
                ));
            }

            // add a behavior in the foreign table to autoupdate the aggregate column
            $foreignTable = $this->getForeignTable($x);
            if ($foreignTable->hasBehavior('concrete_inheritance_parent')) {
                return;
            }

            if (!$foreignTable->hasBehavior('multiple_aggregate_column_relation')) {
                $relationBehavior = new MultipleAggregateColumnRelationBehavior();
                $relationBehavior->setName('multiple_aggregate_column_relation');
                $foreignTable->addBehavior($relationBehavior);
            } else {
                $relationBehavior = $foreignTable->getBehavior('multiple_aggregate_column_relation');
            }

            if (!$relationBehavior->getParameter('relations')) {
                $relations = array();
            } else {
                $relations = $relationBehavior->getParameter('relations');
            }

            if (!isset($relations[$table->getName()])) {
                $relations[$table->getName()] = array(
                    'update_methods' => array()
                );
            }

            $relations[$table->getName()]['update_methods'][] = 'update' . $this->getColumn($x)->getPhpName();

            $relationBehavior->addParameter(array('name' => 'relations', 'value' => $relations));
        } // end for loop
    }

    /**
     * Add the object methods using templates.
     *
     * @param mixed $builder
     */
    public function objectMethods($builder) {
        $script = '';

        // loop through aggregates
        for ($x = 1; $x <= $this->getNbAggregates(); $x++) {
            if (!$this->getAggregateParameter('foreign_table', $x)) {
                throw new InvalidArgumentException(sprintf('You must define a \'foreign_table$x\' parameter for the \'aggregate_column\' behavior in the \'%s\' table', $this->getTable()->getName(), $builder));
            }

            $script .= $this->addObjectCompute($x);
            $script .= $this->addObjectUpdate($x);
        }

        return $script;
    }


    public function postSave($builder)
    {
        $script = '';

        // loop through aggregates
        for ($x = 1; $x <= $this->getNbAggregates(); $x++) {
            if (!$this->getAggregateParameter('foreign_table', $x)) {
                throw new InvalidArgumentException(sprintf('You must define a \'foreign_table$x\' parameter for the \'aggregate_column\' behavior in the \'%s\' table', $this->getTable()->getName(), $builder));
            }

            $script .= $this->renderTemplate('objectPostSave', array(
                'column'     => $this->getColumn($x),
                'columnRefk' => $builder->getRefFKCollVarName($this->getForeignKey($x))
            ));
        }

        return $script;
    }

    /**
     * Build the objectCompute partial template.
     *
     * @param mixed $x index of the template
     */
    protected function addObjectCompute($x) {
        $conditions = array();
        $bindings = array();

        // schema defined condition
        if ($this->getAggregateParameter('condition', $x)) {
            $conditions[] = $this->getAggregateParameter('condition', $x);
        }

        // build the where conditions and bindings
        foreach ($this->getForeignKey($x)->getColumnObjectsMapping() as $index => $columnReference) {
            $conditions[] = $columnReference['local']->getFullyQualifiedName() . ' = :p' . ($index + 1);
            $bindings[$index + 1] = $columnReference['foreign']->getPhpName();
        }

        // determine the table to query
        $database = $this->getTable()->getDatabase();
        $tableName = $database->getTablePrefix() . $this->getAggregateParameter('foreign_table', $x);
        if ($database->getPlatform()->supportsSchemas() && $this->getAggregateParameter('foreign_schema', $x)) {
            $tableName = $this->getAggregateParameter('foreign_schema', $x).'.'.$tableName;
        }

        // build the actual SQL query
        $sql = sprintf(
            'SELECT %s FROM %s WHERE %s',
            $this->getAggregateParameter('expression', $x),
            $database->getPlatform()->quoteIdentifier($tableName),
            implode(' AND ', $conditions)
        );

        return $this->renderTemplate('objectCompute', array(
            'column'   => $this->getColumn($x),
            'sql'      => $sql,
            'bindings' => $bindings,
        ));
    }

    /**
     * Build the objectUpdate partial template.
     *
     * @param mixed $x index of the template
     */
    protected function addObjectUpdate($x) {
        return $this->renderTemplate('objectUpdate', array(
            'column' => $this->getColumn($x),
        ));
    }

    /**
     * Get the foreign table by index.
     *
     * @param mixed $x index of the foreign table
     */
    protected function getForeignTable($x) {
        $database = $this->getTable()->getDatabase();
        $tableName = $database->getTablePrefix() . $this->getAggregateParameter('foreign_table', $x);

        if ($database->getPlatform()->supportsSchemas() && $this->getAggregateParameter('foreign_schema', $x)) {
            $tableName = $this->getAggregateParameter('foreign_schema', $x). '.' . $tableName;
        }

        return $database->getTable($tableName);
    }

    /**
     * Get the foreign key by index.
     *
     * @param mixed $x index of the foreign key
     */
    protected function getForeignKey($x) {
        $foreignTable = $this->getForeignTable($x);
        // let's infer the relation from the foreign table
        $fks = $foreignTable->getForeignKeysReferencingTable($this->getTable()->getName());
        if (!$fks) {
            throw new InvalidArgumentException(sprintf('You must define a foreign key to the \'%s\' table in the \'%s\' table to enable the \'aggregate_column\' behavior', $this->getTable()->getName(), $foreignTable->getName()));
        }

        // If we have more than one FK we must define a "foreign_refphpnameX"  parameter on the behaviour for the FK "refPhpName" attribute
        if(count($fks) > 1) {
            $refphpname = $this->getAggregateParameter('foreign_refphpname', $x);
            if(!$refphpname) {
                throw new InvalidArgumentException(sprintf('You must define a PHP reference name on the \'%s\' behaviour for the \'%s\' table to enable the \'aggregate_column\' behavior', $this->getTable()->getName(), $foreignTable->getName()));
            }
            
            foreach($fks as $fk) {
                if($fk->getAttribute('refPhpName') == $refphpname) {
                    return $fk;
                }
            }
            
            throw new InvalidArgumentException(sprintf('No PHP reference name on the \'%s\' behaviour for the \'%s\' table', $this->getTable()->getName(), $foreignTable->getName()));
        }
        
        return array_shift($fks);
    }

    /**
     * Get the column by index.
     *
     * @param mixed $x index of the column
     */
    protected function getColumn($x) {
        return $this->getTable()->getColumn($this->getAggregateParameter('name', $x));
    }
}
