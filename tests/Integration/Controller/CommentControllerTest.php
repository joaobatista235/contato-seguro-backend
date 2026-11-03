<?php

declare(strict_types=1);

namespace ContatoSeguro\Tests\Integration\Controller;

use ContatoSeguro\Tests\Support\IntegrationTestCase;

class CommentControllerTest extends IntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixture('product_filters.sql');
        $this->loadFixture('comments.sql');
    }

    public function testListsCommentsWithAuthorsLikesAndNestedRepliesInCreationOrder(): void
    {
        $response = $this->request('GET', '/products/1/comments');
        $data = $this->responseData($response);
        $comments = $data['comments'];

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('application/json', $response->getHeaderLine('Content-Type'));
        $this->assertSame(1, $data['product_id']);
        $this->assertSame([1, 2, 7], array_column($comments, 'id'));
        $this->assertSame('First comment', $comments[0]['content']);
        $this->assertSame(7, $comments[0]['admin_user_id']);
        $this->assertSame('Alice', $comments[0]['user_name']);
        $this->assertSame('2023-01-01 10:00:00', $comments[0]['created_at']);
        $this->assertSame(2, $comments[0]['likes']);
        $this->assertFalse($comments[0]['deleted']);
        $reply = $comments[0]['replies'][0];
        $this->assertSame(3, $reply['id']);
        $this->assertSame(1, $reply['parent_id']);
        $this->assertSame('Bob', $reply['user_name']);
        $this->assertSame(1, $reply['likes']);
        $this->assertSame(4, $reply['replies'][0]['id']);
        $this->assertSame([], $reply['replies'][0]['replies']);
        $this->assertTrue($comments[2]['deleted']);
        $this->assertNull($comments[2]['content']);
        $this->assertSame(0, $comments[2]['likes']);
        $this->assertSame(8, $comments[2]['replies'][0]['id']);
    }

    public function testProductWithoutCommentsReturnsAnEmptyHierarchy(): void
    {
        $response = $this->request('GET', '/products/3/comments');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(['product_id' => 3, 'comments' => []], $this->responseData($response));
    }

    public function testCreatesCommentWithTheHeaderAuthorAndPreservesItsText(): void
    {
        $content = "Olá, O'Reilly <script>unsafe</script>";
        $response = $this->request('POST', '/products/1/comments', [
            'content' => '  ' . $content . '  ', 'admin_user_id' => 9, 'product_id' => 6, 'parent_id' => 5,
        ], adminUserId: '8');

        $this->assertSame(201, $response->getStatusCode());
        $id = $this->responseData($response)['id'];
        $comments = $this->responseData($this->request('GET', '/products/1/comments'))['comments'];
        $created = array_column($comments, null, 'id')[$id];
        $this->assertSame($content, $created['content']);
        $this->assertSame(8, $created['admin_user_id']);
        $this->assertSame('Bob', $created['user_name']);
        $this->assertNull($created['parent_id']);
        $this->assertFalse($created['deleted']);
        $this->assertSame(0, $created['likes']);
        $this->assertSame([], $created['replies']);
    }

    public function testCanReplyDirectlyToAnotherReplyAndContinueTheHierarchy(): void
    {
        $first = $this->request(
            'POST',
            '/products/1/comments/4/replies',
            ['content' => 'Fourth level'],
            adminUserId: '8'
        );
        $this->assertSame(201, $first->getStatusCode());
        $firstId = $this->responseData($first)['id'];
        $second = $this->request('POST', '/products/1/comments/' . $firstId . '/replies', ['content' => 'Fifth level']);
        $this->assertSame(201, $second->getStatusCode());
        $secondId = $this->responseData($second)['id'];
        $comments = $this->responseData($this->request('GET', '/products/1/comments'))['comments'];
        $reply = $comments[0]['replies'][0]['replies'][0]['replies'][0];

        $this->assertSame($firstId, $reply['id']);
        $this->assertSame(4, $reply['parent_id']);
        $this->assertSame(8, $reply['admin_user_id']);
        $this->assertSame($secondId, $reply['replies'][0]['id']);
        $this->assertSame($firstId, $reply['replies'][0]['parent_id']);
        $this->assertSame('Fifth level', $reply['replies'][0]['content']);
    }

    public function testAuthorCanRemoveACommentWithoutRemovingOtherUsersReplies(): void
    {
        $response = $this->request('DELETE', '/products/1/comments/1');
        $comments = $this->responseData($this->request('GET', '/products/1/comments'))['comments'];

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($comments[0]['deleted']);
        $this->assertNull($comments[0]['content']);
        $this->assertSame(0, $comments[0]['likes']);
        $this->assertSame('Reply from Bob', $comments[0]['replies'][0]['content']);
        $this->assertSame('Reply to a reply', $comments[0]['replies'][0]['replies'][0]['content']);
        $this->assertSame(404, $this->request('DELETE', '/products/1/comments/1')->getStatusCode());
    }

    public function testCanRemoveOwnReplyButCannotRemoveAnotherAuthorsComment(): void
    {
        $before = $this->responseData($this->request('GET', '/products/1/comments'));
        $response = $this->request('DELETE', '/products/1/comments/2', ['admin_user_id' => 8]);

        $this->assertSame(403, $response->getStatusCode());
        $this->assertArrayHasKey('error', $this->responseData($response));
        $this->assertSame($before, $this->responseData($this->request('GET', '/products/1/comments')));
        $this->assertSame(200, $this->request('DELETE', '/products/1/comments/3', adminUserId: '8')->getStatusCode());
        $comments = $this->responseData($this->request('GET', '/products/1/comments'))['comments'];
        $this->assertTrue($comments[0]['replies'][0]['deleted']);
        $this->assertNull($comments[0]['replies'][0]['content']);
        $this->assertSame(4, $comments[0]['replies'][0]['replies'][0]['id']);
    }

    public function testLikesCountEachAdminOnceAndAllowLikingRepliesAndOwnComments(): void
    {
        $first = $this->request('POST', '/products/1/comments/2/likes');
        $this->assertSame(200, $first->getStatusCode());
        $this->assertSame(['liked' => true], $this->responseData($first));
        $comments = $this->responseData($this->request('GET', '/products/1/comments'))['comments'];
        $this->assertSame(1, $comments[1]['likes']);
        $this->assertSame(
            200,
            $this->request('POST', '/products/1/comments/2/likes', ['admin_user_id' => 8])->getStatusCode()
        );
        $comments = $this->responseData($this->request('GET', '/products/1/comments'))['comments'];
        $this->assertSame(1, $comments[1]['likes']);
        $this->assertSame(
            200,
            $this->request('POST', '/products/1/comments/2/likes', adminUserId: '8')->getStatusCode()
        );
        $this->assertSame(
            200,
            $this->request('POST', '/products/1/comments/4/likes', adminUserId: '8')->getStatusCode()
        );
        $comments = $this->responseData($this->request('GET', '/products/1/comments'))['comments'];
        $this->assertSame(2, $comments[1]['likes']);
        $this->assertSame(1, $comments[0]['replies'][0]['replies'][0]['likes']);
    }

    /** @dataProvider unavailableProducts */
    public function testUnavailableProductsRejectAllCommentRoutes(
        int $productId,
        int $commentId,
        string $adminUserId
    ): void {
        $own = $this->responseData($this->request('GET', '/products/1/comments'));
        $foreign = $this->responseData($this->request('GET', '/products/6/comments', adminUserId: '9'));
        $path = '/products/' . $productId . '/comments';
        $body = ['content' => 'Forbidden'];

        $this->assertSame(404, $this->request('GET', $path, adminUserId: $adminUserId)->getStatusCode());
        $this->assertSame(404, $this->request('POST', $path, $body, $adminUserId)->getStatusCode());
        $this->assertSame(
            404,
            $this->request('POST', $path . '/' . $commentId . '/replies', $body, $adminUserId)->getStatusCode()
        );
        $this->assertSame(
            404,
            $this->request('DELETE', $path . '/' . $commentId, adminUserId: $adminUserId)->getStatusCode()
        );
        $this->assertSame(
            404,
            $this->request('POST', $path . '/' . $commentId . '/likes', adminUserId: $adminUserId)->getStatusCode()
        );
        $this->assertSame($own, $this->responseData($this->request('GET', '/products/1/comments')));
        $this->assertSame(
            $foreign,
            $this->responseData($this->request('GET', '/products/6/comments', adminUserId: '9'))
        );
    }

    public function unavailableProducts(): array
    {
        return [
            'foreign product' => [6, 5, '7'],
            'missing product' => [999, 1, '7'],
            'other company caller' => [1, 1, '9']
        ];
    }

    /** @dataProvider unavailableComments */
    public function testCannotReplyLikeOrRemoveUnavailableComments(int $productId, int $commentId): void
    {
        $before = $this->responseData($this->request('GET', '/products/1/comments'));
        $path = '/products/' . $productId . '/comments/' . $commentId;

        $this->assertSame(404, $this->request('POST', $path . '/replies', ['content' => 'Reply'])->getStatusCode());
        $this->assertSame(404, $this->request('POST', $path . '/likes')->getStatusCode());
        $this->assertSame(404, $this->request('DELETE', $path)->getStatusCode());
        $this->assertSame($before, $this->responseData($this->request('GET', '/products/1/comments')));
    }

    public function unavailableComments(): array
    {
        return ['missing' => [1, 999], 'other product' => [2, 1], 'foreign comment' => [1, 5], 'removed' => [1, 7]];
    }

    /** @dataProvider invalidBodies */
    public function testInvalidContentIsRejectedForCommentsAndRepliesWithoutChangingTheTree(?array $body): void
    {
        $before = $this->responseData($this->request('GET', '/products/1/comments'));

        $this->assertSame(400, $this->request('POST', '/products/1/comments', $body)->getStatusCode());
        $this->assertSame(400, $this->request('POST', '/products/1/comments/1/replies', $body)->getStatusCode());
        $this->assertSame($before, $this->responseData($this->request('GET', '/products/1/comments')));
    }

    public function invalidBodies(): array
    {
        return [
            'missing body' => [null], 'missing content' => [[]], 'null' => [['content' => null]],
            'blank' => [['content' => " \n "]], 'number' => [['content' => 123]],
            'boolean' => [['content' => true]], 'array' => [['content' => ['text']]],
        ];
    }

    /** @dataProvider invalidIds */
    public function testInvalidRouteIdsReturnBadRequest(string $method, string $path): void
    {
        $response = $this->request($method, $path, ['content' => 'Comment']);

        $this->assertSame(400, $response->getStatusCode());
        $this->assertArrayHasKey('error', $this->responseData($response));
    }

    public function invalidIds(): array
    {
        return [
            'list product' => ['GET', '/products/0/comments'],
            'create product' => ['POST', '/products/1.5/comments'],
            'reply product' => ['POST', '/products/0/comments/1/replies'],
            'reply parent' => ['POST', '/products/1/comments/0/replies'],
            'delete product' => ['DELETE', '/products/0/comments/1'],
            'delete comment' => ['DELETE', '/products/1/comments/1.5'],
            'like product' => ['POST', '/products/0/comments/1/likes'],
            'like comment' => ['POST', '/products/1/comments/1 OR 1=1/likes'],
        ];
    }

    /** @dataProvider failedWrites */
    public function testFailedCommentWritesLeaveTheHierarchyAndLikesUnchanged(
        string $trigger,
        string $method,
        string $path
    ): void {
        $before = $this->responseData($this->request('GET', '/products/1/comments'));
        $this->pdo->exec($trigger);

        $response = $this->request($method, $path, ['content' => 'Changed']);

        $this->assertSame(500, $response->getStatusCode());
        $this->assertSame(['error' => 'Internal server error'], $this->responseData($response));
        $this->assertSame($before, $this->responseData($this->request('GET', '/products/1/comments')));
    }

    public function failedWrites(): array
    {
        $comment = "CREATE TRIGGER reject_comment BEFORE INSERT ON product_comment
            BEGIN
                SELECT RAISE(ABORT, 'comment failed');
            END";

        return [
            'create' => [$comment, 'POST', '/products/1/comments'],
            'reply' => [$comment, 'POST', '/products/1/comments/1/replies'],
            'delete' => [
                "CREATE TRIGGER reject_comment_delete BEFORE UPDATE ON product_comment
                    BEGIN
                        SELECT RAISE(ABORT, 'delete failed');
                    END",
                'DELETE',
                '/products/1/comments/1'
            ],
            'like' => [
                "CREATE TRIGGER reject_like BEFORE INSERT ON product_comment_like
                    BEGIN
                        SELECT RAISE(ABORT, 'like failed');
                    END",
                'POST',
                '/products/1/comments/2/likes'
            ],
        ];
    }

    public function testFailedProductDeletionRestoresTheCommentsAndLikes(): void
    {
        $before = $this->responseData($this->request('GET', '/products/1/comments'));
        $this->pdo->exec("CREATE TRIGGER reject_product_log BEFORE INSERT ON product_log
            BEGIN
                SELECT RAISE(ABORT, 'log failed');
            END");

        $response = $this->request('DELETE', '/products/1');

        $this->assertSame(500, $response->getStatusCode());
        $this->assertSame(200, $this->request('GET', '/products/1')->getStatusCode());
        $this->assertSame($before, $this->responseData($this->request('GET', '/products/1/comments')));
    }

    public function testDeletingAProductRemovesItsDiscussionAndNewProductsStartWithoutComments(): void
    {
        $this->assertSame(200, $this->request('DELETE', '/products/1')->getStatusCode());
        $this->assertSame(404, $this->request('GET', '/products/1/comments')->getStatusCode());
        $created = $this->request('POST', '/products', [
            'company_id' => 42, 'title' => 'New product', 'price' => 10,
            'active' => true, 'category_id' => 1,
        ]);
        $this->assertSame(200, $created->getStatusCode());
        $products = $this->responseData($this->request('GET', '/products'));
        $id = array_column($products, null, 'title')['New product']['id'];
        $this->assertSame(
            ['product_id' => $id, 'comments' => []],
            $this->responseData($this->request('GET', '/products/' . $id . '/comments'))
        );
        $foreign = $this->responseData($this->request('GET', '/products/6/comments', adminUserId: '9'));
        $this->assertSame('Foreign comment', $foreign['comments'][0]['content']);
    }
}
