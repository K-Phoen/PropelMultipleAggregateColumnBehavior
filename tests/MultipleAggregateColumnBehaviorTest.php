<?php


/**
 * Tests for MultipleAggregateColumnBehavior class
 *
 * @author Kévin Gomez <contact@kevingomez.fr>
 */
class MultipleAggregateColumnBehaviorTest extends MultipleAggregateColumnBehaviorBaseTest
{
    protected function getSchema()
    {
        return file_get_contents(dirname(__FILE__) . '/fixtures/posts-schema.xml');
    }

    protected function shouldBuildSchema()
    {
        return !class_exists('MultiAggregatePost');
    }

    protected function getConnection()
    {
        return Propel::getConnection(MultiAggregatePostPeer::DATABASE_NAME);
    }

    protected function populatePoll()
    {
        MultiAggregateItemQuery::create()->deleteAll($this->con);
        MultiAggregatePollQuery::create()->deleteAll($this->con);
        $poll = new MultiAggregatePoll();
        $poll->save($this->con);
        $item1 = new MultiAggregateItem();
        $item1->setScore(12);
        $item1->setMultiAggregatePoll($poll);
        $item1->save($this->con);
        $item2 = new MultiAggregateItem();
        $item2->setScore(7);
        $item2->setMultiAggregatePoll($poll);
        $item2->save($this->con);

        return array($poll, $item1, $item2);
    }


    public function testParameters()
    {
        $postTable = MultiAggregatePostPeer::getTableMap();
        $this->assertEquals(count($postTable->getColumns()), 2, 'AggregateColumn adds one column by default');
        $this->assertTrue(method_exists('MultiAggregatePost', 'getNbComments'));

        $pollTable = MultiAggregatePollPeer::getTableMap();
        $this->assertEquals(count($pollTable->getColumns()), 3, 'AggregateColumn adds one column by default');
        $this->assertTrue(method_exists('MultiAggregatePoll', 'getTotalScore'));
        $this->assertTrue(method_exists('MultiAggregatePoll', 'getAverageScore'));
    }

    public function testComputeXXXOneAggregate()
    {
        MultiAggregateCommentQuery::create()->deleteAll($this->con);
        MultiAggregatePostQuery::create()->deleteAll($this->con);

        $post = new MultiAggregatePost();
        $post->save($this->con);
        $this->assertEquals(0, $post->computeNbComments($this->con), 'The compute method returns 0 for objects with no related objects');

        $comment1 = new MultiAggregateComment();
        $comment1->setMultiAggregatePost($post);
        $comment1->save($this->con);
        $this->assertEquals(1, $post->computeNbComments($this->con), 'The compute method computes the aggregate function on related objects');

        $comment2 = new MultiAggregateComment();
        $comment2->setMultiAggregatePost($post);
        $comment2->save($this->con);
        $this->assertEquals(2, $post->computeNbComments($this->con), 'The compute method computes the aggregate function on related objects');

        $comment1->delete($this->con);
        $this->assertEquals(1, $post->computeNbComments($this->con), 'The compute method computes the aggregate function on related objects');
    }

    public function testComputeXXXTwoAggregates()
    {
        MultiAggregateItemQuery::create()->deleteAll($this->con);
        MultiAggregatePollQuery::create()->deleteAll($this->con);

        $poll = new MultiAggregatePoll();
        $poll->save($this->con);
        $this->assertEquals(0, $poll->computeTotalScore($this->con), 'The compute method returns 0 for objects with no related objects');
        $this->assertEquals(0, $poll->computeAverageScore($this->con), 'The compute method returns 0 for objects with no related objects');

        $item1 = new MultiAggregateItem();
        $item1->setScore(20);
        $item1->setMultiAggregatePoll($poll);
        $item1->save($this->con);
        $this->assertEquals(20, $poll->computeTotalScore($this->con), 'The compute method returns 0 for objects with no related objects');
        $this->assertEquals(20, $poll->computeAverageScore($this->con), 'The compute method returns 0 for objects with no related objects');

        $item1 = new MultiAggregateItem();
        $item1->setScore(10);
        $item1->setMultiAggregatePoll($poll);
        $item1->save($this->con);
        $this->assertEquals(30, $poll->computeTotalScore($this->con), 'The compute method returns 0 for objects with no related objects');
        $this->assertEquals(15, $poll->computeAverageScore($this->con), 'The compute method returns 0 for objects with no related objects');
    }

    public function testUpdateXXXOneAggregate()
    {
        MultiAggregateCommentQuery::create()->deleteAll($this->con);
        MultiAggregatePostQuery::create()->deleteAll($this->con);

        $post = new MultiAggregatePost();
        $post->save($this->con);

        $comment = new TestableMultiComment();
        $comment->setMultiAggregatePost($post);
        $comment->save($this->con);
        $this->assertNull($post->getNbComments());
        $post->updateNbComments($this->con);
        $this->assertEquals(1, $post->getNbComments(), 'The update method updates the aggregate column');

        $comment->delete($this->con);
        $this->assertEquals(1, $post->getNbComments());
        $post->updateNbComments($this->con);
        $this->assertEquals(0, $post->getNbComments(), 'The update method updates the aggregate column');
    }

    public function testUpdateXXXTwoAggregates()
    {
        MultiAggregateItemQuery::create()->deleteAll($this->con);
        MultiAggregatePollQuery::create()->deleteAll($this->con);

        $poll = new MultiAggregatePoll();
        $poll->save($this->con);

        $item1 = new TestableMultiItem();
        $item1->setScore(20);
        $item1->setMultiAggregatePoll($poll);
        $item1->save($this->con);
        $this->assertNull($poll->getTotalScore());
        $this->assertNull($poll->getAverageScore());
        $poll->updateTotalScore($this->con);
        $poll->updateAverageScore($this->con);
        $this->assertEquals(20, $poll->getTotalScore(), 'The update method updates the aggregate column');
        $this->assertEquals(20, $poll->getAverageScore(), 'The update method updates the aggregate column');

        $item1 = new TestableMultiItem();
        $item1->setScore(10);
        $item1->setMultiAggregatePoll($poll);
        $item1->save($this->con);
        $this->assertEquals(20, $poll->getTotalScore(), 'The update method updates the aggregate column');
        $this->assertEquals(20, $poll->getAverageScore(), 'The update method updates the aggregate column');
        $poll->updateTotalScore($this->con);
        $poll->updateAverageScore($this->con);
        $this->assertEquals(30, $poll->getTotalScore(), 'The update method updates the aggregate column');
        $this->assertEquals(15, $poll->getAverageScore(), 'The update method updates the aggregate column');
    }

    public function testCreateRelatedOneAggregate()
    {
        MultiAggregateCommentQuery::create()->deleteAll($this->con);
        MultiAggregatePostQuery::create()->deleteAll($this->con);

        $post = new MultiAggregatePost();
        $post->save($this->con);
        $comment1 = new MultiAggregateComment();
        $comment1->save($this->con);
        $this->assertNull($post->getNbComments(), 'Adding a new foreign object does not update the aggregate column');

        $comment2 = new MultiAggregateComment();
        $comment2->setMultiAggregatePost($post);
        $comment2->save($this->con);
        $this->assertEquals(1, $post->getNbComments(), 'Adding a new related object updates the aggregate column');

        $comment3 = new MultiAggregateComment();
        $comment3->setMultiAggregatePost($post);
        $comment3->save($this->con);
        $this->assertEquals(2, $post->getNbComments(), 'Adding a new related object updates the aggregate column');
    }

    public function testCreateRelatedTwoAggregates()
    {
        MultiAggregateItemQuery::create()->deleteAll($this->con);
        MultiAggregatePollQuery::create()->deleteAll($this->con);

        $poll = new MultiAggregatePoll();
        $poll->save($this->con);
        $item1 = new MultiAggregateItem();
        $item1->setScore(10);
        $item1->save($this->con);
        $this->assertNull($poll->getTotalScore(), 'Adding a new foreign object does not update the aggregate column');
        $this->assertNull($poll->getAverageScore(), 'Adding a new foreign object does not update the aggregate column');

        $item2 = new MultiAggregateItem();
        $item2->setScore(10);
        $item2->setMultiAggregatePoll($poll);
        $item2->save($this->con);
        $this->assertEquals(10, $poll->getAverageScore(), 'Adding a new related object updates the aggregate column');
        $this->assertEquals(10, $poll->getTotalScore(), 'Adding a new related object updates the aggregate column');

        $item3 = new MultiAggregateItem();
        $item3->setScore(20);
        $item3->setMultiAggregatePoll($poll);
        $item3->save($this->con);
        $this->assertEquals(15, $poll->getAverageScore(), 'Adding a new related object updates the aggregate column');
        $this->assertEquals(30, $poll->getTotalScore(), 'Adding a new related object updates the aggregate column');
    }

    public function testUpdateRelated()
    {
        list($poll, $item1, $item2) = $this->populatePoll();

        $this->assertEquals(19, $poll->getTotalScore());
        $this->assertEquals(9, $poll->getAverageScore());

        $item1->setScore(10);
        $item1->save($this->con);
        $this->assertEquals(17, $poll->getTotalScore(), 'Updating a related object updates the aggregate column');
        $this->assertEquals(8, $poll->getAverageScore());
    }

    public function testDeleteRelated()
    {
        list($poll, $item1, $item2) = $this->populatePoll();

        $this->assertEquals(19, $poll->getTotalScore());
        $this->assertEquals(9, $poll->getAverageScore());
        $item1->delete($this->con);

        $this->assertEquals(7, $poll->getTotalScore(), 'Deleting a related object updates the aggregate column');
        $this->assertEquals(7, $poll->getAverageScore());

        $item2->delete($this->con);
        $this->assertNull($poll->getTotalScore(), 'Deleting a related object updates the aggregate column');
        $this->assertNull($poll->getAverageScore(), 'Deleting a related object updates the aggregate column');
    }

    public function testUpdateRelatedWithQuery()
    {
        list($poll, $item1, $item2) = $this->populatePoll();

        $this->assertEquals(19, $poll->getTotalScore());
        $this->assertEquals(9, $poll->getAverageScore());

        MultiAggregateItemQuery::create()
            ->update(array('Score' => 4), $this->con);
        $this->assertEquals(8, $poll->getTotalScore(), 'Updating related objects with a query updates the aggregate column');
        $this->assertEquals(4, $poll->getAverageScore());
    }

    public function testUpdateRelatedWithQueryUsingAlias()
    {
        list($poll, $item1, $item2) = $this->populatePoll();

        $this->assertEquals(19, $poll->getTotalScore());
        $this->assertEquals(9, $poll->getAverageScore());

        MultiAggregateItemQuery::create()
            ->setModelAlias('foo', true)
            ->update(array('Score' => 4), $this->con);
        $this->assertEquals(8, $poll->getTotalScore(), 'Updating related objects with a query using alias updates the aggregate column');
        $this->assertEquals(4, $poll->getAverageScore());
    }

    public function testDeleteRelatedWithQuery()
    {
        list($poll, $item1, $item2) = $this->populatePoll();

        $this->assertEquals(19, $poll->getTotalScore());
        $this->assertEquals(9, $poll->getAverageScore());

        MultiAggregateItemQuery::create()
            ->deleteAll($this->con);
        $this->assertNull($poll->getTotalScore(), 'Deleting related objects with a query updates the aggregate column');
        $this->assertNull($poll->getAverageScore(), 'Deleting related objects with a query updates the aggregate column');
    }

    public function testRemoveRelationOneAggregate()
    {
        MultiAggregateCommentQuery::create()->deleteAll($this->con);
        MultiAggregatePostQuery::create()->deleteAll($this->con);

        $post = new MultiAggregatePost();
        $post->save($this->con);

        $comment1 = new MultiAggregateComment();
        $comment1->setMultiAggregatePost($post);
        $comment1->save($this->con);

        $comment2 = new MultiAggregateComment();
        $comment2->setMultiAggregatePost($post);
        $comment2->save($this->con);
        $this->assertEquals(2, $post->getNbComments());

        $comment2->setMultiAggregatePost(null);
        $comment2->save($this->con);

        $this->assertEquals(1, $post->getNbComments(), 'Removing a relation changes the related object aggregate column');
    }

    public function testRemoveRelationTwoAggregates()
    {
        MultiAggregateItemQuery::create()->deleteAll($this->con);
        MultiAggregatePollQuery::create()->deleteAll($this->con);

        $poll = new MultiAggregatePoll();
        $poll->save($this->con);

        $item1 = new MultiAggregateItem();
        $item1->setMultiAggregatePoll($poll);
        $item1->setScore(20);
        $item1->save($this->con);
        $this->assertEquals(20, $poll->getTotalScore());
        $this->assertEquals(20, $poll->getAverageScore());

        $item2 = new MultiAggregateItem();
        $item2->setScore(10);
        $item2->setMultiAggregatePoll($poll);
        $item2->save($this->con);
        $this->assertEquals(30, $poll->getTotalScore());
        $this->assertEquals(15, $poll->getAverageScore());

        $item2->setMultiAggregatePoll(null);
        $item2->save($this->con);

        $this->assertEquals(20, $poll->getTotalScore(), 'Removing a relation changes the related object aggregate column');
    }

    public function testReplaceRelationOneAggregate()
    {
        MultiAggregateCommentQuery::create()->deleteAll($this->con);
        MultiAggregatePostQuery::create()->deleteAll($this->con);

        $post1 = new MultiAggregatePost();
        $post1->save($this->con);
        $post2 = new MultiAggregatePost();
        $post2->save($this->con);

        $comment = new MultiAggregateComment();
        $comment->setMultiAggregatePost($post1);
        $comment->save($this->con);
        $this->assertEquals(1, $post1->getNbComments());
        $this->assertNull($post2->getNbComments());

        $comment->setMultiAggregatePost($post2);
        $comment->save($this->con);
        $this->assertEquals(0, $post1->getNbComments(), 'Replacing a relation changes the related object aggregate column');
        $this->assertEquals(1, $post2->getNbComments(), 'Replacing a relation changes the related object aggregate column');
    }

    public function testReplaceRelationTwoAggregates()
    {
        MultiAggregateItemQuery::create()->deleteAll($this->con);
        MultiAggregatePollQuery::create()->deleteAll($this->con);

        $poll1 = new MultiAggregatePoll();
        $poll1->save($this->con);
        $poll2 = new MultiAggregatePoll();
        $poll2->save($this->con);

        $item = new MultiAggregateItem();
        $item->setScore(1);
        $item->setMultiAggregatePoll($poll1);
        $item->save($this->con);
        $this->assertEquals(1, $poll1->getTotalScore());
        $this->assertEquals(1, $poll1->getAverageScore());
        $this->assertNull($poll2->getTotalScore());
        $this->assertNull($poll2->getAverageScore());

        $item->setMultiAggregatePoll($poll2);
        $item->save($this->con);
        $this->assertEquals(0, $poll1->getTotalScore(), 'Replacing a relation changes the related object aggregate column');
        $this->assertEquals(0, $poll1->getAverageScore(), 'Replacing a relation changes the related object aggregate column');
        $this->assertEquals(1, $poll2->getTotalScore(), 'Replacing a relation changes the related object aggregate column');
        $this->assertEquals(1, $poll2->getAverageScore(), 'Replacing a relation changes the related object aggregate column');
    }

    public function testAddMultipleComments()
    {
        MultiAggregateCommentQuery::create()->deleteAll($this->con);
        MultiAggregatePostQuery::create()->deleteAll($this->con);

        $post1 = new MultiAggregatePost();

        $comment = new MultiAggregateComment();
        $comment->setMultiAggregatePost($post1);

        $comment2 = new MultiAggregateComment();
        $comment2->setMultiAggregatePost($post1);

        $comment3 = new MultiAggregateComment();
        $comment3->setMultiAggregatePost($post1);

        $this->assertNull($post1->getNbComments(), 'The post start with null aggregate column');

        $post1->save($this->con);

        $this->assertEquals(3, $post1->getNbComments(), 'the post has 3 comments');
    }

    public function testAddMultipleItems()
    {
        MultiAggregateItemQuery::create()->deleteAll($this->con);
        MultiAggregatePollQuery::create()->deleteAll($this->con);

        $poll = new MultiAggregatePoll();

        $item = new MultiAggregateItem();
        $item->setScore(10);
        $item->setMultiAggregatePoll($poll);

        $item2 = new MultiAggregateItem();
        $item2->setScore(10);
        $item2->setMultiAggregatePoll($poll);

        $item3 = new MultiAggregateItem();
        $item3->setScore(10);
        $item3->setMultiAggregatePoll($poll);

        $this->assertNull($poll->getTotalScore(), 'The poll start with null aggregate column');
        $this->assertNull($poll->getAverageScore(), 'The poll start with null aggregate column');

        $poll->save($this->con);

        $this->assertEquals(30, $poll->getTotalScore());
        $this->assertEquals(10, $poll->getAverageScore());
    }

    public function testQueryCountOnUpdate()
    {
        MultiAggregateCommentQuery::create()->deleteAll($this->con);
        MultiAggregatePostQuery::create()->deleteAll($this->con);

        $post1 = new TestableMultiPost();
        $comment = new MultiAggregateComment();
        $comment->setMultiAggregatePost($post1);
        $comment2 = new MultiAggregateComment();
        $comment2->setMultiAggregatePost($post1);
        $comment3 = new MultiAggregateComment();
        $comment3->setMultiAggregatePost($post1);
        $post1->save($this->con);
        $this->assertEquals(3, $post1->getNbComments(), 'the post has 3 comments');
        $this->assertEquals(2, $post1->countComputeCall, 'Only two call to count nbComment');

        $post1->countComputeCall = 0;

        $comment4 = new MultiAggregateComment();
        $comment4->setMultiAggregatePost($post1);
        $comment4->save($this->con);

        $this->assertEquals(4, $post1->getNbComments(), 'the post has 4 comments');
        $this->assertEquals(2, $post1->countComputeCall, 'Only two call to count nbComment');

        $post1->countComputeCall = 0;

        $comment5 = new MultiAggregateComment();
        $comment5->setMultiAggregatePost($post1);
        $post1->save($this->con);

        $this->assertEquals(5, $post1->getNbComments(), 'the post has 5 comments');
        $this->assertEquals(2, $post1->countComputeCall, 'Only two call to count nbComment');

        $post1->countComputeCall = 0;
        $post1->save($this->con);

        $this->assertEquals(5, $post1->getNbComments(), 'the post has 5 comments');
        $this->assertEquals(1, $post1->countComputeCall, 'Only one call to count nbComment');
    }
}
