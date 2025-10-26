<?php

namespace dto\historico;

class ApiResponse
{
    public bool $success;
    public ?string $message;
    public mixed $data;

    public function __construct(bool $success, ?string $message = null, mixed $data = null)
    {
        $this->success = $success;
        $this->message = $message;
        $this->data = $data;
    }

    public static function sucesso(?string $message = null, mixed $data = null): self
    {
        return new self(true, $message, $data);
    }

    public static function erro(string $message, mixed $data = null): self
    {
        return new self(false, $message, $data);
    }

    public function toArray(): array
    {
        $response = [
            'success' => $this->success
        ];
        
        if ($this->message !== null) {
            $response['message'] = $this->message;
        }
        
        if ($this->data !== null) {
            $response['data'] = $this->data;
        }
        
        return $response;
    }

    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_UNESCAPED_UNICODE);
    }
}