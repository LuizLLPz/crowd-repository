<?php
namespace modules\core\roteamento;

use DateTime;
use modules\core\tipos\core\controllers\ControllerBase;
use modules\core\tipos\Http\atributos\HttpDelete;
use modules\core\tipos\Http\atributos\HttpGet;
use modules\core\tipos\Http\atributos\HttpPost;
use modules\core\tipos\Http\atributos\HttpPut;
use modules\core\tipos\http\tipos\FuncaoUsuario;
use modules\core\utils\Http;
use modules\core\utils\Utils;
use modules\core\validacoes\Token;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionProperty;

class Roteador
{
    private array $rotas = [];

    public function registrarControladoresPasta(string $pasta): void {
        $classesAntes = get_declared_classes();

        foreach (glob($pasta . '/*.php') as $arquivo) {
            require_once $arquivo;
        }

        $classesDepois = get_declared_classes();

        $novasClasses = array_diff($classesDepois, $classesAntes);

        foreach ($novasClasses as $classe) {
            if (is_subclass_of($classe, \modules\core\tipos\core\controllers\ControllerBase::class))
            {
                try {
                    $this->registrarControlador($classe);
                }
                catch (ReflectionException $e) {
                    echo $e->getMessage();
                }

            }
        }
    }


    /**
     * @throws ReflectionException
     */
    public function registrarControlador(string $classeControlador): void
    {
        $reflexao = new ReflectionClass($classeControlador);
        foreach ($reflexao->getMethods(ReflectionMethod::IS_PUBLIC) as $metodo) {
            foreach ($metodo->getAttributes(HttpGet::class) as $atributo) {
                $instanciaAtributo = $atributo->newInstance();
                $this->rotas['GET'][$instanciaAtributo->path] = [$classeControlador, $metodo->getName()];
            }
            foreach ($metodo->getAttributes(HttpPost::class) as $atributo) {
                $instanciaAtributo = $atributo->newInstance();
                $this->rotas['POST'][$instanciaAtributo->path] = [$classeControlador, $metodo->getName()];
            }
            foreach ($metodo->getAttributes(HttpPut::class) as $atributo) {
                $instanciaAtributo = $atributo->newInstance();
                $this->rotas['PUT'][$instanciaAtributo->path] = [$classeControlador, $metodo->getName()];
            }
            foreach ($metodo->getAttributes(HttpDelete::class) as $atributo) {
                $instanciaAtributo = $atributo->newInstance();
                $this->rotas['DELETE'][$instanciaAtributo->path] = [$classeControlador, $metodo->getName()];
            }
        }
    }

    public function despachar(string $metodoHttp, string $caminho): void
    {
        if (isset($this->rotas[$metodoHttp][$caminho])) {
            [$classe, $metodo] = $this->rotas[$metodoHttp][$caminho];
            $instancia = new $classe();

            $refMetodo = new \ReflectionMethod($classe, $metodo);
            if ($metodoHttp === 'GET') {
                $atributoClasse = HttpGet::class;
            } elseif ($metodoHttp === 'POST') {
                $atributoClasse = HttpPost::class;
            } elseif ($metodoHttp === 'PUT') {
                $atributoClasse = HttpPut::class;
            } elseif ($metodoHttp === 'DELETE') {
                $atributoClasse = HttpDelete::class;
            } else {
                $atributoClasse = null;
            }
            $atributos = $atributoClasse ? $refMetodo->getAttributes($atributoClasse) : [];

            $precisaAuth = true;
            $funcaoUsuario = FuncaoUsuario::USER;

            if (count($atributos) > 0) {
                $atributo = $atributos[0]->newInstance();
                $precisaAuth = $atributo->auth;
                $funcaoUsuario = $atributo->funcaoUsuario;
            }

            $resultado = Token::validarToken();
            if ($resultado) ControllerBase::setDadosUsuarioAutenticado($resultado);

            if ($precisaAuth && !$resultado) {
                Http::HttpResponse(401, "Não autorizado");
            }

            if ($funcaoUsuario === FuncaoUsuario::ADMIN && $resultado->funcao !== FuncaoUsuario::ADMIN) {
                Http::HttpResponse(403, "Você não tem permissão para acessar essa função");
            }

            if ($metodoHttp === 'POST' || $metodoHttp === 'PUT') {
                $parametros = $refMetodo->getParameters();
                $args = [];

                if (count($parametros) > 0) {
                    $parametro = $parametros[0];
                    $tipo = $parametro->getType();
                    $dados = null;
                    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

                    if (strpos($contentType, 'multipart/form-data') !== false) {
                        $dados = $_POST;
                        if ($metodoHttp === "PUT") {
                            $input = file_get_contents('php://input');
                            $dadosPut = Utils::parse_multipart_form_data($input);
                            $_POST = $dadosPut; // Manually populate $_POST
                            $dados = array_merge($dados, $dadosPut);
                        }
                    } else {
                        $input = file_get_contents('php://input');
                        $dados = json_decode($input, true);
                    }

                    if ($tipo && !$tipo->isBuiltin()) {
                        $classeParametro = $tipo->getName();
                        $obj = new $classeParametro();

                        if (is_array($dados)) {
                            foreach ($dados as $chave => $valor) {
                                if (property_exists($obj, $chave)) {
                                    try {
                                        $propriedade = new ReflectionProperty($obj, $chave);
                                        if (!$propriedade->isReadOnly()) {
                                            $tipoPropriedade = $propriedade->getType();
                                            if ($tipoPropriedade && $tipoPropriedade->getName() === DateTime::class) {
                                                if (!empty($valor)) $obj->$chave = new DateTime($valor);
                                            } else {
                                                $obj->$chave = is_string($valor) ? trim($valor) : $valor;
                                            }
                                        }
                                    } catch (\Error $e) {
                                        continue;
                                    }
                                }
                            }
                        }
                        $args[] = $obj;
                    } else {
                        $args[] = $dados[$parametro->getName()] ?? null;
                    }
                }

                $refMetodo->invokeArgs($instancia, $args);
            } else {
                $refMetodo->invoke($instancia);
            }
        } else {
            Http::HttpResponse(404, "Rota não encontrada");
        }
    }
}
