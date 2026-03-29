<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\Http;

class HttpStatusTest extends TestCase
{
    /** @test */
    public function test_http_request_returns_200()
    {
        Http::fake([
            'http://test-ai/*' => Http::response(['status' => 'ok'], 200)
        ]);
        
        $response = Http::get('http://test-ai/health');
        
        $this->assertEquals(200, $response->status());
    }
    
    /** @test */
    public function test_http_request_returns_404()
    {
        Http::fake([
            'http://test-ai/*' => Http::response(null, 404)
        ]);
        
        $response = Http::get('http://test-ai/not-exists');
        
        $this->assertEquals(404, $response->status());
    }
    
    /** @test */
    public function test_http_request_returns_500()
    {
        Http::fake([
            'http://test-ai/*' => Http::response(['error' => 'Server error'], 500)
        ]);
        
        $response = Http::get('http://test-ai/error');
        
        $this->assertEquals(500, $response->status());
    }
    
    /** @test */
    public function test_http_post_sends_json()
    {
        Http::fake([
            'http://test-ai/*' => Http::response(['received' => true], 200)
        ]);
        
        $data = ['name' => 'Test', 'value' => 123];
        $response = Http::post('http://test-ai/process', $data);
        
        Http::assertSent(function ($request) use ($data) {
            return $request->url() == 'http://test-ai/process' &&
                   $request['name'] == $data['name'] &&
                   $request['value'] == $data['value'];
        });
        
        $this->assertEquals(200, $response->status());
    }
}
