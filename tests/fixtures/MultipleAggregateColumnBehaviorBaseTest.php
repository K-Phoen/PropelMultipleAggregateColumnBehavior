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
    protected abstract function getConnection();


    protected function setUp()
    {
        parent::setUp();

        if (!class_exists('AggregatePost')) {
            $this->buildSchema($this->getSchema());
        }

        $this->con = $this->getConnection();
        $this->con->beginTransaction();
    }

    protected function buildSchema($schema)
    {
        $builder = new PropelQuickBuilder();
        $config = $builder->getConfig();
        $builder->setConfig($config);
        $builder->setSchema($schema);

        $builder->build();
    }

    protected function tearDown()
    {
        parent::tearDown();

        // Only commit if the transaction hasn't failed.
        // This is because tearDown() is also executed on a failed tests,
        // and we don't want to call PropelPDO::commit() in that case
        // since it will trigger an exception on its own
        // ('Cannot commit because a nested transaction was rolled back')
        if ($this->con->isCommitable()) {
            $this->con->commit();
        }
    }
}
