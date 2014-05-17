<?php

namespace CL\LunaJsonStore\Test;

use CL\LunaJsonStore\Rel;
use CL\LunaCore\Model\AbstractModel;
use CL\LunaCore\Repo\LinkOne;

class RelOneTest extends AbstractTestCase
{
    public function getRepo()
    {
        return new Repo\User(
            __NAMESPACE__.'\Model\User',
            TEST_DIR.'/User.json'
        );
    }

    /**
     * @covers CL\LunaJsonStore\Rel\One::__construct
     * @covers CL\LunaJsonStore\Rel\One::getKey
     */
    public function testConstruct()
    {
        $user = Repo\User::get();
        $post = Repo\Post::get();

        $rel = new Rel\One('test', $user, $post);

        $this->assertSame($user, $rel->getRepo());
        $this->assertSame($post, $rel->getForeignRepo());
        $this->assertSame('postId', $rel->getKey());

        $rel = new Rel\One('test', $user, $post, ['key' => 'testId']);

        $this->assertSame($user, $rel->getRepo());
        $this->assertSame($post, $rel->getForeignRepo());
        $this->assertSame('testId', $rel->getKey());
    }

    /**
     * @covers CL\LunaJsonStore\Rel\One::areLinked
     */
    public function testAreLinked()
    {
        $rel = new Rel\One('test', Repo\User::get(), Repo\Post::get());

        $user = new Model\User(['id' => 2]);
        $post = new Model\Post(['id' => 5]);

        $this->assertFalse($rel->areLinked($user, $post));

        $user->postId = 5;

        $this->assertTrue($rel->areLinked($user, $post));
    }

    public function dataHasForeign()
    {
        return [
            [[new Model\User(['id' => 2]), new Model\User(['id' => 2])], false],
            [[new Model\User(['id' => 2, 'postId' => 4]), new Model\User(['id' => 2])], true],
        ];
    }

    /**
     * @covers CL\LunaJsonStore\Rel\One::hasForeign
     * @dataProvider dataHasForeign
     */
    public function testHasForeign($models, $expected)
    {
        $rel = new Rel\One('test', Repo\User::get(), Repo\Post::get());

        $this->assertEquals($expected, $rel->hasForeign($models));
    }

    /**
     * @covers CL\LunaJsonStore\Rel\One::loadForeign
     */
    public function testLoadForeign()
    {
        $repo = $this->getMock(
            __NAMESPACE__.'\Repo\Post',
            ['getContents'],
            [__NAMESPACE__.'\Model\Post', TEST_DIR.'/Post.json']
        );

        $repo
            ->expects($this->once())
            ->method('getContents')
            ->will($this->returnValue([
                3 => ['id' => 3],
                5 => ['id' => 5],
                8 => ['id' => 8],
            ]));

        $rel = new Rel\One('test', Repo\User::get(), $repo);

        $models = [
            new Model\User(['id' => 2, 'postId' => 3]),
            new Model\User(['id' => 2, 'postId' => 8])
        ];

        $result = $rel->loadForeign($models);

        $this->assertCount(2, $result);

        foreach ([0 => 3, 1 => 8] as $index => $id) {
            $this->assertInstanceOf(__NAMESPACE__.'\Model\Post', $result[$index]);
            $this->assertTrue($result[$index]->isPersisted());
            $this->assertEquals($id, $result[$index]->id);
        }
    }

    /**
     * @covers CL\LunaJsonStore\Rel\One::update
     */
    public function testUpdate()
    {
        $rel = new Rel\One('test', Repo\User::get(), Repo\Post::get());

        $user = new Model\User(['id' => 2]);
        $post = new Model\Post(['id' => 5]);

        $rel->update($user, new LinkOne($rel, $post));

        $this->assertEquals(5, $user->postId);
    }

    /**
     * @covers CL\LunaJsonStore\Rel\One::update
     * @expectedException InvalidArgumentException
     */
    public function testUpdateWithWrongArguments()
    {
        $rel = new Rel\One('test', Repo\User::get(), Repo\Post::get());

        $link = $this->getMockForAbstractClass('CL\LunaCore\Repo\LinkMany', [], '', false);

        $user = new Model\User(['id' => 2]);

        $rel->update($user, $link);
    }
}
