<?php
/**
 * CleverReach WordPress Integration.
 *
 * @package CleverReach
 */

namespace CleverReach\WordPress\Components\Utility;

use CleverReach\WordPress\Components\Utility\Update\Update_Schema;

/**
 * Class Versioned_File_Reader
 *
 * @package CleverReach\WordPress\Components\Utility
 */
class Versioned_File_Reader {

	const MIGRATION_FILE_PREFIX = 'migration.v.';

	/**
	 * Migrations directory
	 *
	 * @var string
	 */
	private $migrations_directory;

	/**
	 * Database version
	 *
	 * @var string
	 */
	private $version;

	/**
	 * Array of sorted files for execution
	 *
	 * @var array
	 */
	private $sorted_files_for_execution = array();

	/**
	 * Pointer for files
	 *
	 * @var int
	 */
	private $pointer = 0;

	/**
	 * Versioned_File_Reader constructor.
	 *
	 * @param string $migration_directory Migrations directory.
	 * @param string $version             Database version.
	 */
	public function __construct( $migration_directory, $version ) {
		$this->migrations_directory = $migration_directory;
		$this->version              = $version;
	}

	/**
	 * Read next file from list of files for execution
	 *
	 * @return Update_Schema|null
	 */
	public function read_next() {
		if ( ! $this->has_next() ) {
			return null;
		}

		include_once $this->migrations_directory . $this->sorted_files_for_execution[ $this->pointer ];
		$version = $this->get_file_version($this->sorted_files_for_execution[$this->pointer]);
		$class_name = $this->get_class_name($version);
		$this->pointer ++;

		return class_exists($class_name) ? new $class_name : null;
	}

	/**
	 * Checks if there is a next file from list of files for execution
	 *
	 * @return bool
	 */
	public function has_next() {
		if ( empty( $this->sorted_files_for_execution ) ) {
			$this->sort_files();
		}

		return isset( $this->sorted_files_for_execution[ $this->pointer ] );
	}

	/**
	 * Sort and filter files for execution
	 */
	private function sort_files() {
		$files = array_diff( scandir( $this->migrations_directory, 0 ), array( '.', '..' ) );
		if ( $files ) {
			$self = $this;
			usort(
				$files,
				function ( $file1, $file2 ) use ( $self ) {
					$file_1_version = $self->get_file_version( $file1 );
					$file_2_version = $self->get_file_version( $file2 );

					return version_compare( $file_1_version, $file_2_version );
				}
			);

			foreach ( $files as $file ) {
				$file_version = $this->get_file_version( $file );
				if ( version_compare( $this->version, $file_version, '<' ) ) {
					$this->sorted_files_for_execution[] = $file;
				}
			}
		}
	}

	/**
	 * Get file version based on file name
	 *
	 * @param string $file File name.
	 *
	 * @return string
	 */
	private function get_file_version( $file ) {
		return str_ireplace( array( self::MIGRATION_FILE_PREFIX, '.php' ), '', $file );
	}

	/**
	 * @param string $version
	 *
	 * @return string
	 */
	private function get_class_name($version) {
		return 'CleverReach\\WordPress\\Database\\Migrations\\Migration_' . str_replace('.', '_', $version);
	}
}
