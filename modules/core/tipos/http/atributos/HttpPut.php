<?php
namespace modules\core\tipos\Http\atributos;

#[\Attribute]
class HttpPut {
    public string $rota;
    public bool $auth;
    public string $funcaoUsuario;

    public function __construct(string $rota, bool $auth = true, string $funcaoUsuario = 'USER') {
        $this->rota = $rota;
        $this->auth = $auth;
        $this->funcaoUsuario = $funcaoUsuario;
    }
}
