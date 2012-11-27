<?php

class TestablePost extends AggregatePost
{
    public $countComputeCall = 0;

    public function computeNbComments(PropelPDO $con)
    {
        $this->countComputeCall++;

        return parent::computeNbComments($con);
    }

}
