<?php

class TestableMultiPost extends MultiAggregatePost
{
    public $countComputeCall = 0;

    public function computeNbComments(PropelPDO $con)
    {
        $this->countComputeCall++;

        return parent::computeNbComments($con);
    }

}
