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
 * @author     François Zaninotto
 * @version    $Revision: 1785 $
 * @package    propel.generator.behavior.aggregate_column
 */
class MultipleAggregateColumnRelationBehavior extends Behavior
{

    // default parameters value
    protected $parameters = array(
        'relations' => array(),
    );

    public function postSave($builder)
    {
        $script = '';

        foreach ($this->getParameter('relations') as $foreign_table => $relation) {
            $relationName = $this->getRelationName($builder, $foreign_table);
            $script .= "\$this->updateRelated{$relationName}(\$con);";
        }

        return $script;
    }

    // no need for a postDelete() hook, since delete() uses Query::delete(),
    // which already has a hook

    public function objectAttributes($builder)
    {
        $script = '';

        foreach ($this->getParameter('relations') as $foreign_table => $relation) {
            $relationName = $this->getRelationName($builder, $foreign_table);
            $script .= "protected \$old{$relationName};
";
        }

        return $script;
    }

    public function objectMethods($builder)
    {
        return $this->addObjectUpdateRelated($builder);
    }

    protected function addObjectUpdateRelated($builder)
    {
        $script = '';

        foreach ($this->getParameter('relations') as $foreign_table => $relation) {
            $relationName = $this->getRelationName($builder, $foreign_table);

            $script .= $this->renderTemplate('objectUpdateRelated', array(
                'relationName'  => $relationName,
                'variableName'  => self::lcfirst($relationName),
                'updateMethods' => $relation['update_methods'],
            ));
        }

        return $script;
    }

    public function objectFilter(&$script, $builder)
    {
        foreach ($this->getParameter('relations') as $foreign_table => $relation) {
            $relationName = $this->getRelationName($builder, $foreign_table);
            $relatedClass = $this->getForeignTable($foreign_table)->getPhpName();

            $search = "	public function set{$relationName}({$relatedClass} \$v = null)
        {";
            $replace = $search . "
            // aggregate_column_relation behavior
            if (null !== \$this->a{$relationName} && \$v !== \$this->a{$relationName}) {
                \$this->old{$relationName} = \$this->a{$relationName};
            }";
            $script = str_replace($search, $replace, $script);
        }

    }

    public function preUpdateQuery($builder)
    {
        return $this->getFindRelated($builder);
    }

    public function preDeleteQuery($builder)
    {
        return $this->getFindRelated($builder);
    }

    protected function getFindRelated($builder)
    {
        $script = '';

        foreach ($this->getParameter('relations') as $foreign_table => $relation) {
            $relationName = $this->getRelationName($builder, $foreign_table);

            $script .= "\$this->findRelated{$relationName}s(\$con);";
        }

        return $script;
    }

    public function postUpdateQuery($builder)
    {
        return $this->getUpdateRelated($builder);
    }

    public function postDeleteQuery($builder)
    {
        return $this->getUpdateRelated($builder);
    }

    protected function getUpdateRelated($builder)
    {
        $script = '';

        foreach ($this->getParameter('relations') as $foreign_table => $relation) {
            $relationName = $this->getRelationName($builder, $foreign_table);

            $script .= "\$this->updateRelated{$relationName}s(\$con);";
        }

        return $script;
    }

    public function queryMethods($builder)
    {
        $script = '';
        $script .= $this->addQueryFindRelated($builder);
        $script .= $this->addQueryUpdateRelated($builder);

        return $script;
    }

    protected function addQueryFindRelated($builder)
    {
        $script = '';

        foreach ($this->getParameter('relations') as $foreign_table => $relation) {
            $relationName = $this->getRelationName($builder, $foreign_table);
            $foreignKey = $this->getForeignKey($foreign_table);

            $script .= $this->renderTemplate('queryFindRelated', array(
                'foreignTable'     => $this->getForeignTable($foreign_table),
                'relationName'     => $relationName,
                'variableName'     => self::lcfirst($relationName),
                'foreignQueryName' => $foreignKey->getForeignTable($foreign_table)->getPhpName() . 'Query',
                'refRelationName'  => $builder->getRefFKPhpNameAffix($foreignKey),
            ));
        }

        return $script;
    }

    protected function addQueryUpdateRelated($builder)
    {
        $script = '';

        foreach ($this->getParameter('relations') as $foreign_table => $relation) {
            $relationName = $this->getRelationName($builder, $foreign_table);

            $script .= $this->renderTemplate('queryUpdateRelated', array(
                'relationName'     => $relationName,
                'variableName'     => self::lcfirst($relationName),
                'updateMethodNames' => $relation['update_methods'],
            ));
        }

        return $script;
    }

    protected function getForeignTable($table)
    {
        return $this->getTable()->getDatabase()->getTable($table);
    }

    protected function getForeignKey($foreign_table_name)
    {
        $foreignTable = $this->getForeignTable($foreign_table_name);
        // let's infer the relation from the foreign table
        $fks = $this->getTable()->getForeignKeysReferencingTable($foreignTable->getName());
        // FIXME doesn't work when more than one fk to the same table
        return array_shift($fks);
    }

    protected function getRelationName($builder, $foreign_table)
    {
        return $builder->getFKPhpNameAffix($this->getForeignKey($foreign_table));
    }

    protected static function lcfirst($input)
    {
        // no lcfirst in php<5.3...
        $input[0] = strtolower($input[0]);
        return $input;
    }
}
