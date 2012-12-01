<?php


/**
 * Base class for the behavior's tests.
 *
 * @author Kévin Gomez <contact@kevingomez.fr>
 */
abstract class MultipleAggregateColumnBehaviorBaseTest extends \PHPUnit_Framework_TestCase
{
    protected $con;


    protected abstract function getSchema();
    protected abstract function shouldBuildSchema();
    protected abstract function getConnection();


    protected function setUp()
    {
        parent::setUp();

        if ($this->shouldBuildSchema()) {
            $this->buildSchema($this->getSchema());
        }

        $this->con = $this->getConnection();
    }

    protected function buildSchema($schema)
    {
        $builder = new PropelQuickBuilder();
        $config = $builder->getConfig();
        $builder->setConfig($config);
        $builder->setSchema($schema);

        $builder->build();
    }
}
