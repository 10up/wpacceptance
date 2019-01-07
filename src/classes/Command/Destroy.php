<?php
/**
 * Stops and destroys a running WP Acceptance environment
 *
 * @package wpacceptance
 */

namespace WPAcceptance\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

use WPAcceptance\Log;
use WPAcceptance\EnvironmentFactory;

/**
 * Destroy command class
 */
class Destroy extends Command {

	/**
	 * Setup up command
	 */
	protected function configure() {
		$this->setName( 'destroy' );
		$this->setDescription( 'Stops and destroys a running WP Acceptance environment' );

		$this->addArgument( 'environment_id', InputArgument::OPTIONAL, 'Environment ID.' );
		$this->addOption( 'all', false, InputOption::VALUE_NONE, 'Destroy all environments.' );
	}

	/**
	 * Execute command
	 *
	 * @param  InputInterface  $input Console input
	 * @param  OutputInterface $output Console output
	 */
	protected function execute( InputInterface $input, OutputInterface $output ) {
		Log::instance()->setOutput( $output );

		$environment_id = $input->getArgument( 'environment_id' );
		$all            = $input->getOption( 'all' );

		Log::instance()->write( 'Destroying environment(s).', 0 );

		if ( ! empty( $all ) ) {
			EnvironmentFactory::destroyAll();
		} elseif ( ! empty( $environment_id ) ) {
			EnvironmentFactory::destroy( $input->getArgument( 'environment_id' ) );
		} else {
			Log::instance()->write( 'You must provide an environment ID or --all.', 0, 'error' );

			return 1;
		}

		Log::instance()->write( 'Done.', 0, 'success' );
	}

}
