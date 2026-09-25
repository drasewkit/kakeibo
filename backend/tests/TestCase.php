<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
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
