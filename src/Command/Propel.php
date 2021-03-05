<?php

namespace Spartan\Rest\Command;

use Laminas\Code\Generator\ClassGenerator;
use Laminas\Code\Generator\PropertyGenerator;
use Laminas\Code\Generator\PropertyValueGenerator;
use Spartan\Console\Command;
use Spartan\Rest\Generator\Mysql;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Generate Resource Classes from Propel Models
 *
 * @package Spartan\Provision
 * @author  Iulian N. <iulian@spartanphp.com>
 * @license LICENSE MIT
 */
class Propel extends Command
{
    protected function configure()
    {
        $this->withSynopsis('rest:propel', 'Create resource classes from sql tables')
             ->withOption('env', 'ENV file with configuration', './config/.env')
             ->withOption('overwrite', 'Overwrite previous files', false)
             ->withOption('table', 'Run on a single table', false);
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int|null|void
     * @throws \InvalidArgumentException
     * @throws \Nette\InvalidArgumentException
     * @throws \Nette\InvalidStateException
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $env    = parse_ini_file($input->getOption('env'), false, INI_SCANNER_RAW);
        $remote = '_';

        if ($env['DB_REMOTE'] ?? 0) {
            $remote = '_REMOTE_';
        }

        $host = $env["DB{$remote}HOST"];
        $port = $env["DB{$remote}PORT"];
        $name = $env["DB_NAME"];
        $user = $env["DB{$remote}USER"];
        $pass = $env["DB{$remote}PASS"];
        $app  = preg_replace('/[^a-zA-Z0-9_]+/', '\\', $env['APP_NAME']);

        $generator = new Mysql(
            new \PDO(
                "mysql:host={$host};port={$port};dbname={$name}",
                $user,
                $pass
            ),
            [
                'namespace' => "{$app}\\Domain\\Resource",
                'tables'    => $input->getOption('table') ? [$input->getOption('table')] : [],
            ]
        );

        $schemas = $generator->generate();

        $dst = "./src/Domain/Resource";

        if (!file_exists($dst)) {
            mkdir($dst, 0777, true);
        }

        $namespace = "{$app}\\Domain\\Resource";

        foreach ($schemas as $tableName => $schema) {
            $className    = $this->pascalCase($tableName);
            $resourceFile = $dst . "/{$className}.php";
            $tableClass   = $this->buildClass($schema, $className, $namespace);

//            if (file_exists($resourceFile)) {
//                require_once $resourceFile;
//                $class = ClassType::from(Author::class);
//                echo (string)$class;
//                exit;
//            }

            if (file_exists($resourceFile) && $input->getOption('overwrite') === false) {
                $output->writeln("<danger>Resource exists: {$className}</danger>");
            } else {
                file_put_contents($resourceFile, "<?php\n\n" . $this->prettify((string)$tableClass->generate()));
                $output->writeln("<success>Resource created: {$className}</success>");
            }
        }

        return 0;
    }

    /**
     * @param array  $schema
     * @param string $className
     * @param string $namespace
     *
     * @return ClassGenerator
     */
    protected function buildClass(array $schema, string $className, string $namespace)
    {
        $propel = '\\' . substr($namespace, 0, strrpos($namespace, '\\')) . "\Model\\{$className}::class";

        return (new ClassGenerator())
            ->setName($className)
            ->setNamespaceName($namespace)
            ->addUse('Spartan\Rest\Adapter\Propel\Resource')
            ->setExtendedClass('Spartan\Rest\Adapter\Propel\Resource')
            ->addConstantFromGenerator(
                new PropertyGenerator(
                    'PROPEL',
                    new PropertyValueGenerator(
                        $propel,
                        PropertyValueGenerator::TYPE_CONSTANT
                    ),
                    PropertyGenerator::FLAG_CONSTANT
                )
            )
            ->addConstant('SCHEMA', $schema);
    }

    protected function namespace($modelClass)
    {
        $namespace = explode('\\', $modelClass);
        array_splice($namespace, -2, 2, 'Resource');

        return implode('\\', $namespace);
    }

    protected function prettify(string $class)
    {
        return strtr(
            $class,
            [
                "\t"        => '    ',
                "=> ['type" => "=> [\n\t\t\t'type",
            ]
        );
    }

    /**
     * @param string $subject
     * @param string $whitelist
     *
     * @return string|string[]
     */
    protected function pascalCase(string $subject, $whitelist = '/[^a-zA-Z0-9 ]+/')
    {
        return str_replace(
            ' ',
            '',
            mb_convert_case(
                strtolower(
                    preg_replace(
                        $whitelist,
                        ' ',
                        transliterator_transliterate(
                            'Any-Latin; Latin-ASCII;',
                            $subject
                        )
                    )
                ),
                MB_CASE_TITLE,
                'UTF-8'
            )
        );
    }
}
