<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    /**
     * テスト用DB（phpunit.xmlのDB_DATABASE）の名前
     */
    private const TESTING_DATABASE = 'testing';

    public function createApplication()
    {
        $app = parent::createApplication();

        // RefreshDatabaseは全テーブルを作り直すため、テスト用DB以外に接続していたら実行前に止める。
        // 環境変数が漏れ込むとphpunit.xmlの<env>は上書きされず、開発用DBが消える事故が実際に起きた
        $config = $app->make('config');
        $database = $config->get('database.connections.'.$config->get('database.default').'.database');
        if ($database !== self::TESTING_DATABASE) {
            throw new RuntimeException(
                "テストの接続先DBが「{$database}」になっています。テスト用DB「".self::TESTING_DATABASE.'」以外では実行しません。'
            );
        }

        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();

        // SanctumのEnsureFrontendRequestsAreStatefulは、Referer/Originヘッダが
        // sanctum.statefulのドメインに一致した場合だけセッションミドルウェアを通す。
        // テストはこれらのヘッダを送らないため、付与しないとセッションが張られず、
        // 本番と同じSPAクッキー認証の経路を検証できない
        // （$request->session()が「Session store not set on request」で落ちる）。
        $this->withHeader('Origin', 'http://localhost');
    }
}
