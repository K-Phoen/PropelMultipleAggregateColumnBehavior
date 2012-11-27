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
        return file_get_contents(dirname(__FILE__) . '/fixtures/posts-compatibility-schema.xml');
    }

    protected function getConnection()
    {
        return Propel::getConnection(AggregatePostPeer::DATABASE_NAME);
    }

    protected function populatePoll()
    {
        AggregateItemQuery::create()->deleteAll($this->con);
        AggregatePollQuery::create()->deleteAll($this->con);
        $poll = new AggregatePoll();
        $poll->save($this->con);
        $item1 = new AggregateItem();
        $item1->setScore(12);
        $item1->setAggregatePoll($poll);
        $item1->save($this->con);
        $item2 = new AggregateItem();
        $item2->setScore(7);
        $item2->setAggregatePoll($poll);
        $item2->save($this->con);

        return array($poll, $item1, $item2);
    }


    public function testParameters()
    {
        $postTable = AggregatePostPeer::getTableMap();
        $this->assertEquals(count($postTable->getColumns()), 2, 'AggregateColumn adds one column by default');
        $this->assertTrue(method_exists('AggregatePost', 'getNbComments'));
    }

    public function testCompute()
    {
        AggregateCommentQuery::create()->deleteAll($this->con);
        AggregatePostQuery::create()->deleteAll($this->con);
        $post = new AggregatePost();
        $post->save($this->con);
        $this->assertEquals(0, $post->computeNbComments($this->con), 'The compute method returns 0 for objects with no related objects');
        $comment1 = new AggregateComment();
        $comment1->setAggregatePost($post);
        $comment1->save($this->con);
        $this->assertEquals(1, $post->computeNbComments($this->con), 'The compute method computes the aggregate function on related objects');
        $comment2 = new AggregateComment();
        $comment2->setAggregatePost($post);
        $comment2->save($this->con);
        $this->assertEquals(2, $post->computeNbComments($this->con), 'The compute method computes the aggregate function on related objects');
        $comment1->delete($this->con);
        $this->assertEquals(1, $post->computeNbComments($this->con), 'The compute method computes the aggregate function on related objects');
    }

    public function testUpdate()
    {
        AggregateCommentQuery::create()->deleteAll($this->con);
        AggregatePostQuery::create()->deleteAll($this->con);
        $post = new AggregatePost();
        $post->save($this->con);
        $comment = new TestableComment();
        $comment->setAggregatePost($post);
        $comment->save($this->con);
        $this->assertNull($post->getNbComments());
        $post->updateNbComments($this->con);
        $this->assertEquals(1, $post->getNbComments(), 'The update method updates the aggregate column');
        $comment->delete($this->con);
        $this->assertEquals(1, $post->getNbComments());
        $post->updateNbComments($this->con);
        $this->assertEquals(0, $post->getNbComments(), 'The update method updates the aggregate column');
    }

    public function testCreateRelated()
    {
        AggregateCommentQuery::create()->deleteAll($this->con);
        AggregatePostQuery::create()->deleteAll($this->con);
        $post = new AggregatePost();
        $post->save($this->con);
        $comment1 = new AggregateComment();
        $comment1->save($this->con);
        $this->assertNull($post->getNbComments(), 'Adding a new foreign object does not update the aggregate column');
        $comment2 = new AggregateComment();
        $comment2->setAggregatePost($post);
        $comment2->save($this->con);
        $this->assertEquals(1, $post->getNbComments(), 'Adding a new related object updates the aggregate column');
        $comment3 = new AggregateComment();
        $comment3->setAggregatePost($post);
        $comment3->save($this->con);
        $this->assertEquals(2, $post->getNbComments(), 'Adding a new related object updates the aggregate column');
    }

    public function testUpdateRelated()
    {
        list($poll, $item1, $item2) = $this->populatePoll();
        $this->assertEquals(19, $poll->getTotalScore());
        $item1->setScore(10);
        $item1->save($this->con);
        $this->assertEquals(17, $poll->getTotalScore(), 'Updating a related object updates the aggregate column');
    }

    public function testDeleteRelated()
    {
        list($poll, $item1, $item2) = $this->populatePoll();
        $this->assertEquals(19, $poll->getTotalScore());
        $item1->delete($this->con);
        $this->assertEquals(7, $poll->getTotalScore(), 'Deleting a related object updates the aggregate column');
        $item2->delete($this->con);
        $this->assertNull($poll->getTotalScore(), 'Deleting a related object updates the aggregate column');
    }

    public function testUpdateRelatedWithQuery()
    {
        list($poll, $item1, $item2) = $this->populatePoll();
        $this->assertEquals(19, $poll->getTotalScore());
        AggregateItemQuery::create()
            ->update(array('Score' => 4), $this->con);
        $this->assertEquals(8, $poll->getTotalScore(), 'Updating related objects with a query updates the aggregate column');
    }

    public function testUpdateRelatedWithQueryUsingAlias()
    {
        list($poll, $item1, $item2) = $this->populatePoll();
        $this->assertEquals(19, $poll->getTotalScore());
        AggregateItemQuery::create()
            ->setModelAlias('foo', true)
            ->update(array('Score' => 4), $this->con);
        $this->assertEquals(8, $poll->getTotalScore(), 'Updating related objects with a query using alias updates the aggregate column');
    }

    public function testDeleteRelatedWithQuery()
    {
        list($poll, $item1, $item2) = $this->populatePoll();
        $this->assertEquals(19, $poll->getTotalScore());
        AggregateItemQuery::create()
            ->deleteAll($this->con);
        $this->assertNull($poll->getTotalScore(), 'Deleting related objects with a query updates the aggregate column');
    }

    /*
    // @todo: fails on sqlite, search why (does not seem the be the behavior's fault).
    public function testDeleteRelatedWithQueryUsingAlias()
    {
        list($poll, $item1, $item2) = $this->populatePoll();
        $this->assertEquals(19, $poll->getTotalScore());
        AggregateItemQuery::create()
            ->setModelAlias('foo', true)
            ->filterById($item1->getId())
            ->delete($this->con);
        $this->assertEquals(7, $poll->getTotalScore(), 'Deleting related objects with a query using alias updates the aggregate column');
    }
    */

    public function testRemoveRelation()
    {
        AggregateCommentQuery::create()->deleteAll($this->con);
        AggregatePostQuery::create()->deleteAll($this->con);

        $post = new AggregatePost();
        $post->save($this->con);

        $comment1 = new AggregateComment();
        $comment1->setAggregatePost($post);
        $comment1->save($this->con);

        $comment2 = new AggregateComment();
        $comment2->setAggregatePost($post);
        $comment2->save($this->con);
        $this->assertEquals(2, $post->getNbComments());

        $comment2->setAggregatePost(null);
        $comment2->save($this->con);

        $this->assertEquals(1, $post->getNbComments(), 'Removing a relation changes the related object aggregate column');
    }

    public function testReplaceRelation()
    {
        AggregateCommentQuery::create()->deleteAll($this->con);
        AggregatePostQuery::create()->deleteAll($this->con);
        $post1 = new AggregatePost();
        $post1->save($this->con);
        $post2 = new AggregatePost();
        $post2->save($this->con);
        $comment = new AggregateComment();
        $comment->setAggregatePost($post1);
        $comment->save($this->con);
        $this->assertEquals(1, $post1->getNbComments());
        $this->assertNull($post2->getNbComments());
        $comment->setAggregatePost($post2);
        $comment->save($this->con);
        $this->assertEquals(0, $post1->getNbComments(), 'Replacing a relation changes the related object aggregate column');
        $this->assertEquals(1, $post2->getNbComments(), 'Replacing a relation changes the related object aggregate column');
    }

    public function testAddMultipleComments()
    {
        AggregateCommentQuery::create()->deleteAll($this->con);
        AggregatePostQuery::create()->deleteAll($this->con);

        $post1 = new AggregatePost();

        $comment = new AggregateComment();
        $comment->setAggregatePost($post1);

        $comment2 = new AggregateComment();
        $comment2->setAggregatePost($post1);

        $comment3 = new AggregateComment();
        $comment3->setAggregatePost($post1);

        $this->assertNull($post1->getNbComments(), 'The post start with null aggregate column');

        $post1->save($this->con);

        $this->assertEquals(3, $post1->getNbComments(), 'the post has 3 comments');
    }

    public function testQueryCountOnUpdate()
    {
        AggregateCommentQuery::create()->deleteAll($this->con);
        AggregatePostQuery::create()->deleteAll($this->con);

        $post1 = new TestablePost();
        $comment = new AggregateComment();
        $comment->setAggregatePost($post1);
        $comment2 = new AggregateComment();
        $comment2->setAggregatePost($post1);
        $comment3 = new AggregateComment();
        $comment3->setAggregatePost($post1);
        $post1->save($this->con);
        $this->assertEquals(3, $post1->getNbComments(), 'the post has 3 comments');
        $this->assertEquals(2, $post1->countComputeCall, 'Only two call to count nbComment');

        $post1->countComputeCall = 0;

        $comment4 = new AggregateComment();
        $comment4->setAggregatePost($post1);
        $comment4->save($this->con);

        $this->assertEquals(4, $post1->getNbComments(), 'the post has 4 comments');
        $this->assertEquals(2, $post1->countComputeCall, 'Only two call to count nbComment');

        $post1->countComputeCall = 0;

        $comment5 = new AggregateComment();
        $comment5->setAggregatePost($post1);
        $post1->save($this->con);

        $this->assertEquals(5, $post1->getNbComments(), 'the post has 5 comments');
        $this->assertEquals(2, $post1->countComputeCall, 'Only two call to count nbComment');

        $post1->countComputeCall = 0;
        $post1->save($this->con);

        $this->assertEquals(5, $post1->getNbComments(), 'the post has 5 comments');
        $this->assertEquals(1, $post1->countComputeCall, 'Only one call to count nbComment');
    }
}
