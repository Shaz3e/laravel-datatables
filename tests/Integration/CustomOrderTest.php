<?php

namespace Yajra\DataTables\Tests\Integration;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Yajra\DataTables\DataTables;
use Yajra\DataTables\Tests\Models\Post;
use Yajra\DataTables\Tests\TestCase;

class CustomOrderTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    function it_can_order_with_custom_order()
    {
        $response = $this->getJsonResponse([
            'order'  => [
                [
                    'column' => 1,
                    'dir'    => 'asc',
                ],
            ],
            'length' => 10,
            'start'  => 0,
            'draw'   => 1,
        ]);

        $response->assertJson([
            'draw'            => 1,
            'recordsTotal'    => 60,
            'recordsFiltered' => 60,
        ]);

        $this->assertEquals($response->json()['data'][0]['user']['id'], collect($response->json()['data'])->pluck('user.id')->max());
    }

    protected function getJsonResponse(array $params = [])
    {
        $data = [
            'columns' => [
                ['data' => 'user.id', 'name' => 'user.name', 'searchable' => 'true', 'orderable' => 'true'],
                ['data' => 'user.email', 'name' => 'user.email', 'searchable' => 'true', 'orderable' => 'true'],
                ['data' => 'title', 'name' => 'posts.title', 'searchable' => 'true', 'orderable' => 'true'],
            ],
        ];

        return $this->call('GET', '/relations/belongsTo', array_merge($data, $params));
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->app['router']->get('/relations/belongsTo', function (DataTables $datatables) {
            return $datatables->eloquent(Post::with('user')->select('posts.*'))
                              ->orderColumn('user.email', function ($query, $order) {
                                  $query->orderBy('users.id', $order == 'desc' ? 'asc' : 'desc');
                              })->toJson();
        });
    }
}
