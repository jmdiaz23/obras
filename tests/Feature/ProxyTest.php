<?php

namespace Tests\Feature;

use Tests\TestCase;

class ProxyTest extends TestCase
{
    public function test_detecta_https_y_la_ip_real_detras_del_proxy_local(): void
    {
        $response = $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->withHeaders([
                'X-Forwarded-Proto' => 'https',
                'X-Forwarded-For' => '200.1.2.3',
                'X-Forwarded-Host' => 'obras.test',
            ])
            ->get('/login');

        $response->assertOk();

        $this->assertTrue(request()->isSecure());
        $this->assertSame('200.1.2.3', request()->ip());
    }
}
