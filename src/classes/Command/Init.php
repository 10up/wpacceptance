<?php
/**
 * Init command - creates wpassure.json
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
use WPAssure\Utils;
use WPAssure\Config;

/**
 * Init command class
 */
class Init extends Command {

	/**
	 * Setup up command
	 */
	protected function configure() {
		$this->setName( 'init' );
		$this->setDescription( 'Initialize WP Assure on a project.' );

		$this->addOption( 'path', null, InputOption::VALUE_REQUIRED, 'Path to location to initialize WP Assure.' );
	}

	/**
	 * Execute command
	 *
	 * @param  InputInterface  $input Console input
	 * @param  OutputInterface $output Console output
	 */
	protected function execute( InputInterface $input, OutputInterface $output ) {
		Log::instance()->setOutput( $output );

		$config_array = [];

		$config_array['path'] = $input->getOption( 'path' );

		if ( empty( $config_array['path'] ) ) {
			$config_array['path'] = getcwd();
		}

		$config_array['path'] = Utils\normalize_path( $config_array['path'] );

		$helper = $this->getHelper( 'question' );

		$name_question = new Question( 'Project Slug (letters, numbers, _, and - only): ' );
		$name_question->setValidator( '\WPAssure\Utils\slug_validator' );
		$config_array['name'] = $helper->ask( $input, $output, $name_question );

		$tests = $helper->ask( $input, $output, new Question( 'Tests location (defaults to ./tests/*.php): ', './tests/*.php' ) );

		$config['tests'] = $tests;

		$config_array['test_clean_db'] = $helper->ask( $input, $output, new ConfirmationQuestion( 'Do you want to require a fresh database for each test? This will make tests slower but is needed if you intend on modifying the database during tests. (yes or no) ', true ) );

		$config = new Config( $config_array );
		$config->write();

		Log::instance()->write( $config['path'] . 'wpassure.json created.', 0, 'success' );

		$tests = Utils\normalize_path( rtrim( $tests, '*' ) );

		if ( ! file_exists( $tests ) && mkdir( $tests ) ) {
			Log::instance()->write( $tests . ' directory created.', 0, 'success' );
		}

		if ( ! file_exists( $tests . 'ExampleTest.php' ) ) {
			copy( WPASSURE_DIR . '/example/ExampleTest.php', $tests . 'ExampleTest.php' );

			Log::instance()->write( $tests . 'ExampleTest.php created.', 0, 'success' );
		}

		Log::instance()->write( 'Done.', 0, 'success' );
	}

}
