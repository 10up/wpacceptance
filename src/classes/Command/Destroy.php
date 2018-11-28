<?php
/**
 * Stops and destroys a running WP Assure environment
 *
 * @package wpassure
 */

namespace WPAssure\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

use WPAssure\Log;
use WPAssure\EnvironmentFactory;

/**
 * Destroy command class
 */
class Destroy extends Command {

	/**
	 * Setup up command
	 */
	protected function configure() {
		$this->setName( 'destroy' );
		$this->setDescription( 'Stops and destroys a running WP Assure environment' );

		$this->addArgument( 'environment_id', InputArgument::REQUIRED, 'Environment ID.' );
	}

	/**
	 * Execute command
	 *
	 * @param  InputInterface  $input Console input
	 * @param  OutputInterface $output Console output
	 */
	protected function execute( InputInterface $input, OutputInterface $output ) {
		Log::instance()->setOutput( $output );

		EnvironmentFactory::destroy( $input->getArgument( 'environment_id' ) );

		Log::instance()->write( 'Done.', 0, 'success' );
	}

}
