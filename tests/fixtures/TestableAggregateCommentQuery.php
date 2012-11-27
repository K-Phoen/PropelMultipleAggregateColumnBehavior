<?php

class TestableAggregateCommentQuery extends AggregateCommentQuery
{
    public static function create($modelAlias = null, $criteria = null)
    {
        return new TestableAggregateCommentQuery();
    }

    // overrides the parent basePreDelete() to bypass behavior hooks
    protected function basePreDelete(PropelPDO $con)
    {
        return $this->preDelete($con);
    }

    // overrides the parent basePostDelete() to bypass behavior hooks
    protected function basePostDelete($affectedRows, PropelPDO $con)
    {
        return $this->postDelete($affectedRows, $con);
    }

}
