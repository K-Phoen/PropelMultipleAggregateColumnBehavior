<?php

class TestableItem extends AggregateItem
{
    // overrides the parent save() to bypass behavior hooks
    public function save(PropelPDO $con = null)
    {
        $con->beginTransaction();
        try {
            $affectedRows = $this->doSave($con);
            AggregateCommentPeer::addInstanceToPool($this);
            $con->commit();

            return $affectedRows;
        } catch (PropelException $e) {
            $con->rollBack();
            throw $e;
        }
    }

    // overrides the parent delete() to bypass behavior hooks
    public function delete(PropelPDO $con = null)
    {
        $con->beginTransaction();
        try {
            TestableAggregateCommentQuery::create()
                ->filterByPrimaryKey($this->getPrimaryKey())
                ->delete($con);
            $con->commit();
            $this->setDeleted(true);
        } catch (PropelException $e) {
            $con->rollBack();
            throw $e;
        }
    }

}
