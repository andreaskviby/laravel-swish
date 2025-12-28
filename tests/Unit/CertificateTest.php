<?php

namespace AndreasKviby\LaravelSwish\Tests\Unit;

use AndreasKviby\LaravelSwish\Exceptions\CertificateException;
use AndreasKviby\LaravelSwish\Support\Certificate;
use PHPUnit\Framework\TestCase;

class CertificateTest extends TestCase
{
    private string $tempCertPath;
    private string $tempRootCertPath;

    protected function setUp(): void
    {
        parent::setUp();

        // Skapa temporära certifikatfiler för test
        // Vi använder fake innehåll eftersom vi endast testar Certificate-klassens
        // validering och konfiguration, inte faktisk SSL-funktionalitet
        $this->tempCertPath = sys_get_temp_dir() . '/test-client-cert.pem';
        $this->tempRootCertPath = sys_get_temp_dir() . '/test-root-cert.pem';

        file_put_contents($this->tempCertPath, 'fake cert content');
        file_put_contents($this->tempRootCertPath, 'fake root cert content');
    }

    protected function tearDown(): void
    {
        if (file_exists($this->tempCertPath)) {
            unlink($this->tempCertPath);
        }

        if (file_exists($this->tempRootCertPath)) {
            unlink($this->tempRootCertPath);
        }

        parent::tearDown();
    }

    public function test_can_create_certificate()
    {
        $cert = new Certificate(
            $this->tempCertPath,
            'password123',
            $this->tempRootCertPath
        );

        $this->assertEquals($this->tempCertPath, $cert->getClientCertPath());
        $this->assertEquals('password123', $cert->getClientCertPassword());
        $this->assertEquals($this->tempRootCertPath, $cert->getRootCertPath());
    }

    public function test_throws_exception_if_client_cert_not_found()
    {
        $this->expectException(CertificateException::class);

        new Certificate('/path/to/nonexistent/cert.pem');
    }

    public function test_throws_exception_if_root_cert_not_found()
    {
        $this->expectException(CertificateException::class);

        new Certificate(
            $this->tempCertPath,
            null,
            '/path/to/nonexistent/root-cert.pem'
        );
    }

    public function test_to_guzzle_config_returns_correct_format()
    {
        $cert = new Certificate(
            $this->tempCertPath,
            'password123',
            $this->tempRootCertPath
        );

        $config = $cert->toGuzzleConfig();

        $this->assertIsArray($config);
        $this->assertArrayHasKey('cert', $config);
        $this->assertArrayHasKey('verify', $config);

        $this->assertEquals([$this->tempCertPath, 'password123'], $config['cert']);
        $this->assertEquals($this->tempRootCertPath, $config['verify']);
    }

    public function test_to_guzzle_config_without_password()
    {
        $cert = new Certificate($this->tempCertPath);

        $config = $cert->toGuzzleConfig();

        $this->assertEquals($this->tempCertPath, $config['cert']);
    }
}
