<?php
namespace modules\core\roteamento;

use DateTime;
use modules\core\atributos\HttpPost;
use modules\core\tipos\ControllerBase;
use modules\core\validacoes\Token;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use modules\core\atributos\HttpGet;
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
            if (is_subclass_of($classe, \modules\core\tipos\ControllerBase::class))
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
        }
    }

    public function despachar(string $metodoHttp, string $caminho): void
    {
        if (isset($this->rotas[$metodoHttp][$caminho])) {
            [$classe, $metodo] = $this->rotas[$metodoHttp][$caminho];
            $instancia = new $classe();

            $refMetodo = new \ReflectionMethod($classe, $metodo);
            $atributoClasse = $metodoHttp === 'GET' ? HttpGet::class : HttpPost::class;
            $atributos = $refMetodo->getAttributes($atributoClasse);

            $precisaAuth = true;

            if (count($atributos) > 0) {
                $atributo = $atributos[0]->newInstance();
                $precisaAuth = $atributo->auth;
            }

            $resultado = Token::validarToken();

            if ($precisaAuth && !$resultado) {
                http_response_code(401);
                echo json_encode(["erro" => "Não autorizado"]);
                return;
            }
            ControllerBase::setDadosUsuarioAutenticado($resultado);

            if ($metodoHttp === 'POST') {
                $parametros = $refMetodo->getParameters();
                $args = [];

                if (count($parametros) > 0) {
                    $input = file_get_contents('php://input');
                    $dados = json_decode($input, true);
                    $parametro = $parametros[0];
                    $tipo = $parametro->getType();

                    if ($tipo && !$tipo->isBuiltin()) {
                        $classeParametro = $tipo->getName();
                        $obj = new $classeParametro();

                        foreach ($dados as $chave => $valor) {
                            if (property_exists($obj, $chave)) {
                                $tipoPropriedade = new ReflectionProperty($obj, $chave)->getType();
                                if ($tipoPropriedade && $tipoPropriedade->getName() === DateTime::class) {
                                    $obj->$chave = new DateTime($valor);
                                } else {
                                    $obj->$chave = $valor;
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
            http_response_code(404);
            echo json_encode(["error" => "Rota não encontrada"]);
        }
    }

}
