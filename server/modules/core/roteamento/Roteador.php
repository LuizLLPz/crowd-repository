<?php
namespace modules\core\roteamento;

use modules\core\atributos\HttpPost;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use modules\core\atributos\HttpGet;

class Roteador
{
    private array $rotas = [];

    public function registrarControladoresPasta(string $pasta): void {
        // Obter as classes declaradas antes de incluir os arquivos
        $classesAntes = get_declared_classes();

        // Incluir todos os arquivos PHP da pasta
        foreach (glob($pasta . '/*.php') as $arquivo) {
            require_once $arquivo;
        }

        // Obter as classes declaradas após incluir os arquivos
        $classesDepois = get_declared_classes();

        // Determinar as novas classes adicionadas
        $novasClasses = array_diff($classesDepois, $classesAntes);

        // Filtrar e registrar as classes que estendem a classe base
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
                /** @var HttpGet $instanciaAtributo */
                $instanciaAtributo = $atributo->newInstance();
                $this->rotas['GET'][$instanciaAtributo->path] = [$classeControlador, $metodo->getName()];
            }
            foreach ($metodo->getAttributes(HttpPost::class) as $atributo) {
                /** @var HttpGet $instanciaAtributo */
                $instanciaAtributo = $atributo->newInstance();
                $this->rotas['POST'][$instanciaAtributo->path] = [$classeControlador, $metodo->getName()];
            }
        }
    }

    public function despachar(string $metodoHttp, string $caminho): void
    {
        if (isset($this->rotas[$metodoHttp][$caminho])) {
            [$classe, $metodo] = $this->rotas[$metodoHttp][$caminho];
            new $classe()->$metodo();
        } else {
            // Lógica para rota não encontrada
            http_response_code(404);
            echo "<h1>404</h1><p>Caminho não encontrado.</p>";
        }
    }
}
