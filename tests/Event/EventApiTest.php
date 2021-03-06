<?php
namespace Tests\Event;

class EventApiTest extends \PHPUnit_Framework_TestCase
{
    private $mockConfig;
    private $mockCache;
    private $mockDbEvent;

    public function setUp()
    {
        $this->mockConfig = array('apiUrl' => 'http://example.com');
        $this->mockCache = $this->getMock(
            'Application\CacheService'
        );

        $this->mockDbEvent = $this->getMock(
            'Event\EventDb',
            null,
            array($this->mockCache)
        );

    }

    public function testDefaultGetCollectionParametersAreSet()
    {
        $mockEvent = $this->getMock(
            'Event\EventApi',
            array('apiGet'),
            array($this->mockConfig, null, $this->mockDbEvent)
        );

        $expectedParams = ['resultsperpage' => 10, 'start' => 1];
        $mockEvent->expects($this->once())
            ->method('apiGet')
            ->with('http://example.com/v2.1/events', $expectedParams)
            ->will($this->returnValue(json_encode(array('events' => array(), 'meta' => array()))));

        $mockEvent->getCollection();
    }

    public function testGetCollectionWithLimitSetsParamsCorrectly()
    {
        $mockEvent = $this->getMock(
            'Event\EventApi',
            array('apiGet'),
            array($this->mockConfig, null, $this->mockDbEvent)
        );

        $expectedParams = ['resultsperpage' => 75, 'start' => 1];
        $mockEvent->expects($this->once())
            ->method('apiGet')
            ->with('http://example.com/v2.1/events', $expectedParams)
            ->will($this->returnValue(json_encode(array('events' => array(), 'meta' => array()))));

        $mockEvent->getCollection(75);
    }

    public function testGetCollectionWithPageValueSetsParamsCorrectly()
    {
        $mockEvent = $this->getMock(
            'Event\EventApi',
            array('apiGet'),
            array($this->mockConfig, null, $this->mockDbEvent)
        );

        $expectedParams = ['resultsperpage' => 32, 'start' => 6];
        $mockEvent->expects($this->once())
            ->method('apiGet')
            ->with('http://example.com/v2.1/events', $expectedParams)
            ->will($this->returnValue(json_encode(array('events' => array(), 'meta' => array()))));

        $mockEvent->getCollection(32, 6);
    }

    public function testGetCollectionWithFilterSetsAllParamsCorrectly()
    {
        $mockEvent = $this->getMock(
            'Event\EventApi',
            array('apiGet'),
            array($this->mockConfig, null, $this->mockDbEvent)
        );

        $expectedParams = ['resultsperpage' => 16, 'start' => 3, 'filter' => 'samoflange'];
        $mockEvent->expects($this->once())
            ->method('apiGet')
            ->with('http://example.com/v2.1/events', $expectedParams)
            ->will($this->returnValue(json_encode(array('events' => array(), 'meta' => array()))));

        $mockEvent->getCollection(16, 3, 'samoflange');
    }

    public function testGetCollectionWithVerboseSetsAllParamsCorrectly()
    {
        $mockEvent = $this->getMock(
            'Event\EventApi',
            array('apiGet'),
            array($this->mockConfig, null, $this->mockDbEvent)
        );

        $expectedParams = ['resultsperpage' => 16, 'start' => 3, 'verbose' => 'yes'];
        $mockEvent->expects($this->once())
            ->method('apiGet')
            ->with('http://example.com/v2.1/events', $expectedParams)
            ->will($this->returnValue(json_encode(array('events' => array(), 'meta' => array()))));

        $mockEvent->getCollection(16, 3, null, true);
    }

    public function testGetCollectionWithQueryParamsPassesThemThroughCorrectly()
    {
        $mockEvent = $this->getMock(
            'Event\EventApi',
            array('apiGet'),
            array($this->mockConfig, null, $this->mockDbEvent)
        );

        $expectedParams = ['resultsperpage' => 16, 'start' => 3, 'title' => 'test', 'tags' => 'php'];
        $mockEvent->expects($this->once())
            ->method('apiGet')
            ->with('http://example.com/v2.1/events', $expectedParams)
            ->will($this->returnValue(json_encode(array('events' => array(), 'meta' => array()))));

        $mockEvent->getCollection(16, 3, null, false, array('title' => 'test', 'tags' => 'php'));
    }

    /**
     * Test that addComment() posts the correct data to the API
     */
    public function testAddCommentPostsAComment()
    {
        // The object containing the event details (in this case, we only
        // need to mock the comments_uri and its getter
        $mockEventObj = $this->getMock(
            'Event\EventEntity',
            array('getCommentsUri'),
            array(
                (object) array('comments_uri'=>'http://example.com/comments/123')
            )
        );

        $mockEventObj->expects($this->once())
            ->method('getCommentsUri')
            ->will($this->returnValue('http://example.com/comments/123'));


        // We need to create the Event API class, and mock the call to the
        // joind.in API to return a known result and check we're making the
        // correct call
        $mockEventApi = $this->getMock(
            'Event\EventApi',
            array('apiPost'),
            array($this->mockConfig, null, $this->mockDbEvent)
        );

        $mockEventApi->expects($this->once())
            ->method('apiPost')
            ->with(
                'http://example.com/comments/123',
                array('comment'=>'comment')
            )
            ->will($this->returnValue(array('201', 'result')));

        // The test
        $this->assertTrue(
            $mockEventApi->addComment($mockEventObj, 'comment')
        );
    }

    /**
     * If the API is down, then post comment should throw an exception
     */
    public function testPostCommentThrowsExceptionIfAPIReturnsBadStatus()
    {
        // The object containing the event details (in this case, we only
        // need to mock the comments_uri and its getter
        $mockEventObj = $this->getMock(
            'Event\EventEntity',
            array('getCommentsUri'),
            array(
                (object) array('comments_uri'=>'http://example.com/comments/123')
            )
        );

        $mockEventObj->expects($this->once())
            ->method('getCommentsUri')
            ->will($this->returnValue('http://example.com/comments/123'));


        // We need to create the Event API class, and mock the call to the
        // joind.in API to return a known (failed) result and check we're making the
        // correct call
        $mockEventApi = $this->getMock(
            'Event\EventApi',
            array('apiPost'),
            array($this->mockConfig, null, $this->mockDbEvent)
        );

        $mockEventApi->expects($this->once())
            ->method('apiPost')
            ->with(
                'http://example.com/comments/123',
                array('comment'=>'comment')
            )
            ->will($this->returnValue(array('500', 'no result')));

        // The test
        $this->setExpectedException('Exception');
        $mockEventApi->addComment($mockEventObj, 'comment');
    }

    public function testAttendThrowsExceptionIfAPIReturnsBadStatus()
    {
        $mockEventObj = $this->getMock(
            'Event\EventEntity',
            array('getApiUriToMarkAsAttending'),
            array(
                (object) array('attending_uri'=>'http://example.com/events/1/attending')
            )
        );

        $mockEventObj->expects($this->once())
            ->method('getApiUriToMarkAsAttending')
            ->will($this->returnValue('http://example.com/events/1/attending'));


        $mockEventApi = $this->getMock(
            'Event\EventApi',
            array('apiPost'),
            array($this->mockConfig, null, $this->mockDbEvent)
        );

        $mockEventApi->expects($this->once())
            ->method('apiPost')
            ->with(
                'http://example.com/events/1/attending'
            )
            ->will($this->returnValue(array('500', 'no result')));

        $this->setExpectedException('Exception');
        $mockEventApi->attend($mockEventObj);
    }

    public function testDefaultGetTalkCommentsParametersAreSet()
    {
        $comment_uri = 'http://example.com/v2.1/events/1/talk_comments';
        $mockEvent = $this->getMock(
            'Event\EventApi',
            array('apiGet'),
            array($this->mockConfig, null, $this->mockDbEvent)
        );

        $mockEvent->expects($this->once())
            ->method('apiGet')
            ->with('http://example.com/v2.1/events/1/talk_comments?resultsperpage=10&start=1')
            ->will($this->returnValue(json_encode(array('comments' => array(), 'meta' => array()))));

        $mockEvent->getTalkComments($comment_uri);
    }

    public function testGetTalkCommentsWithLimitSetsParamsCorrectly()
    {
        $comment_uri = 'http://example.com/v2.1/events/1/talk_comments';
        $mockEvent = $this->getMock(
            'Event\EventApi',
            array('apiGet'),
            array($this->mockConfig, null, $this->mockDbEvent)
        );

        $mockEvent->expects($this->once())
            ->method('apiGet')
            ->with('http://example.com/v2.1/events/1/talk_comments?resultsperpage=75&start=1')
            ->will($this->returnValue(json_encode(array('comments' => array(), 'meta' => array()))));

        $mockEvent->getTalkComments($comment_uri, 75);
    }

    public function testGetTalkCommentsWithStartValueSetsParamsCorrectly()
    {
        $comment_uri = 'http://example.com/v2.1/events/1/talk_comments';
        $mockEvent = $this->getMock(
            'Event\EventApi',
            array('apiGet'),
            array($this->mockConfig, null, $this->mockDbEvent)
        );

        $mockEvent->expects($this->once())
            ->method('apiGet')
            ->with('http://example.com/v2.1/events/1/talk_comments?resultsperpage=32&start=6')
            ->will($this->returnValue(json_encode(array('comments' => array(), 'meta' => array()))));

        $mockEvent->getTalkComments($comment_uri, 32, 6);
    }

    public function testGetTalkCommentsWithVerboseSetsAllParamsCorrectly()
    {
        $comment_uri = 'http://example.com/v2.1/events/1/talk_comments';
        $mockEvent = $this->getMock(
            'Event\EventApi',
            array('apiGet'),
            array($this->mockConfig, null, $this->mockDbEvent)
        );

        $mockEvent->expects($this->once())
            ->method('apiGet')
            ->with('http://example.com/v2.1/events/1/talk_comments?resultsperpage=16&start=3&verbose=yes')
            ->will($this->returnValue(json_encode(array('comments' => array(), 'meta' => array()))));

        $mockEvent->getTalkComments($comment_uri, 16, 3, true);
    }
}
