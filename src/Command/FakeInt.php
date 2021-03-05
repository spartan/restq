<?php

namespace Spartan\Rest\Command;

use Spartan\Console\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * FakeInt Command
 *
 * @package Spartan\Rest
 * @author  Iulian N. <iulian@spartanphp.com>
 * @license LICENSE MIT
 */
class FakeInt extends Command
{
    protected function configure()
    {
        $this->withSynopsis('rest:fake', 'Run a FakeInt simulation')
             ->withArgument('int', 'Int to encode/decode');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        Command\Config\Env::load();

        $value     = $input->getArgument('int');
        $transform = new \Spartan\Rest\Transform\FakeInt();

        $output->writeln('Decoded: ' . $transform->request($value));
        $output->writeln('Encoded: ' . $transform->response($value));

        return 0;
    }
}
