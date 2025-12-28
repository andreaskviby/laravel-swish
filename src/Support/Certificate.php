<?php

namespace AndreasKviby\LaravelSwish\Support;

use AndreasKviby\LaravelSwish\Exceptions\CertificateException;

/**
 * Hanterar Swish-certifikat för autentisering
 */
class Certificate
{
    /**
     * @var string Sökväg till klientcertifikat
     */
    protected string $clientCertPath;

    /**
     * @var string|null Lösenord för klientcertifikat
     */
    protected ?string $clientCertPassword;

    /**
     * @var string|null Sökväg till root-certifikat
     */
    protected ?string $rootCertPath;

    /**
     * Skapa en ny Certificate-instans
     *
     * @param string $clientCertPath
     * @param string|null $clientCertPassword
     * @param string|null $rootCertPath
     * @throws CertificateException
     */
    public function __construct(
        string $clientCertPath,
        ?string $clientCertPassword = null,
        ?string $rootCertPath = null
    ) {
        $this->clientCertPath = $clientCertPath;
        $this->clientCertPassword = $clientCertPassword;
        $this->rootCertPath = $rootCertPath;

        $this->validate();
    }

    /**
     * Validera att certifikaten finns och är läsbara
     *
     * @throws CertificateException
     */
    protected function validate(): void
    {
        if (!file_exists($this->clientCertPath)) {
            throw new CertificateException(
                "Klientcertifikat hittades inte: {$this->clientCertPath}"
            );
        }

        if (!is_readable($this->clientCertPath)) {
            throw new CertificateException(
                "Kan inte läsa klientcertifikat: {$this->clientCertPath}"
            );
        }

        if ($this->rootCertPath && !file_exists($this->rootCertPath)) {
            throw new CertificateException(
                "Root-certifikat hittades inte: {$this->rootCertPath}"
            );
        }

        if ($this->rootCertPath && !is_readable($this->rootCertPath)) {
            throw new CertificateException(
                "Kan inte läsa root-certifikat: {$this->rootCertPath}"
            );
        }
    }

    /**
     * Hämta klientcertifikatets sökväg
     *
     * @return string
     */
    public function getClientCertPath(): string
    {
        return $this->clientCertPath;
    }

    /**
     * Hämta klientcertifikatets lösenord
     *
     * @return string|null
     */
    public function getClientCertPassword(): ?string
    {
        return $this->clientCertPassword;
    }

    /**
     * Hämta root-certifikatets sökväg
     *
     * @return string|null
     */
    public function getRootCertPath(): ?string
    {
        return $this->rootCertPath;
    }

    /**
     * Returnera certifikatkonfiguration för Guzzle
     *
     * @return array
     */
    public function toGuzzleConfig(): array
    {
        $config = [
            'cert' => $this->clientCertPassword
                ? [$this->clientCertPath, $this->clientCertPassword]
                : $this->clientCertPath,
        ];

        if ($this->rootCertPath) {
            $config['verify'] = $this->rootCertPath;
        }

        return $config;
    }
}
