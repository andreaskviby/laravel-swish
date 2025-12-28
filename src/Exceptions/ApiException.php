<?php

namespace AndreasKviby\LaravelSwish\Exceptions;

/**
 * Exception för API-anrop
 */
class ApiException extends SwishException
{
    /**
     * @var array|null Feldetaljer från Swish API
     */
    protected ?array $errorDetails = null;

    /**
     * Sätt feldetaljer från API-svar
     *
     * @param array $details
     * @return self
     */
    public function setErrorDetails(array $details): self
    {
        $this->errorDetails = $details;
        return $this;
    }

    /**
     * Hämta feldetaljer
     *
     * @return array|null
     */
    public function getErrorDetails(): ?array
    {
        return $this->errorDetails;
    }
}
