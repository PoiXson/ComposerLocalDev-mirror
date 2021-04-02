<?php
/*
 * PoiXson ComposerLocalDev - Symlink vendor data to your local workspace
 * @copyright 2019
 * @license GPL-3
 * @author lorenzo at poixson.com
 * @link http://poixson.com/
 */
namespace pxn\ComposerLocalDev;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Script\ScriptEvents;
use Composer\Plugin\PluginInterface;
use Composer\EventDispatcher\EventSubscriberInterface;


class Plugin implements PluginInterface, EventSubscriberInterface {

	const LOCAL_DEV_CONFIG = 'localdev.json';

	protected $composer;
	protected $io;
	protected $repoManager;

	protected $config;



	public function activate(Composer $composer, IOInterface $io) {
		$this->composer = $composer;
		$this->io = $io;
		$this->repoManager = $composer->getRepositoryManager();
		// load config
		$config_path = '';
		for ($depth=0; $depth<3; $depth++) {
			$config_path = \str_repeat('../', $depth).self::LOCAL_DEV_CONFIG;
			if (\file_exists($config_path))
				break;
			$config_path = '';
		}
		if (empty($config_path))
			$depth = 0;
		$this->config = new Config(config_file: $config_path, depth: $depth);
		$this->config->load();
		if ($this->isDev()) {
			$this->info('<info>Development mode</info>');
			$this->debug('Found localdev file: '.$this->config->getConfigPath());
		}
	}
	public function deactivate(Composer $composer, IOInterface $io) {
	}
	public function uninstall(Composer $composer, IOInterface $io) {
	}



	public static function getSubscribedEvents(): array {
		return [
			ScriptEvents::PRE_INSTALL_CMD  => ['handleEvent', \PHP_INT_MAX],
			ScriptEvents::PRE_UPDATE_CMD   => ['handleEvent', \PHP_INT_MAX],
			ScriptEvents::POST_INSTALL_CMD => ['handleEvent', \PHP_INT_MIN],
			ScriptEvents::POST_UPDATE_CMD  => ['handleEvent', \PHP_INT_MIN],
		];
	}
	public function handleEvent($script_event): void {
		$event_name = $script_event->getName();
		switch ($event_name) {
		case ScriptEvents::PRE_INSTALL_CMD:
		case ScriptEvents::PRE_UPDATE_CMD:
			$this->restore();
			break;
		case ScriptEvents::POST_INSTALL_CMD:
		case ScriptEvents::POST_UPDATE_CMD:
			$this->apply();
			break;
		default:
			$this->error('Unknown event: '.$event_name, __FILE__, __LINE__);
			exit(1);
		}
	}



	public function apply() {
		if ( ! $this->isDev() ) {
			$this->info('<info>Skipping symlinking</info>');
			return;
		}
//TODO: remove this, not needed
/*
		$optimize = false;
		{
			$input  = $this->getInput();
			$composerConfig = $this->composer->getConfig();
			// classmap-authoritative
			if ($input->getOption('classmap-authoritative')) {
				$this->info('<info>classmap-authoritative enabled by console</info>');
				$optimize = true;
			} else
			if ($composerConfig->get('classmap-authoritative')) {
				$this->info('<info>classmap-authoritative enabled by composer config</info>');
				$optimize = true;
			}
			// optimize-autoloader
			if ($input->getOption('optimize-autoloader')) {
				$this->info('<info>optimize-autoloader enabled by console</info>');
				$optimize = true;
			} else
			if ($composerConfig->get('optimize-autoloader')) {
				$this->info('<info>optimize-autoloader enabled by composer config</info>');
				$optimize = true;
			}
			$this->debug('Optimize: '.($optimize ? 'yes' : 'no'));
			if ($optimize) {
				$this->info('<info>Skipping symlinking</info>');
				return;
			}
		}
*/
		// dev paths
		$first = true;
		$paths = $this->config->getPaths();
		$cwd = \getcwd();
		foreach ($paths as $namespace => $dev_path) {
			if (empty($dev_path)) continue;
			// check dev path exists
			if ( ! \is_dir("$cwd/$dev_path") ) continue;
			$namespace_path = self::VendorPathFromNamespace($namespace);
			// check vendor path exists
			if ( ! \is_dir("$cwd/$namespace_path") ) continue;
			if ($first) {
				$first = false;
				$this->info("<info>Creating symlinks to local dev..</info>");
			}
			$this->info("Symlinking.. <info>$namespace_path</info> => <info>$dev_path</info>");
			$p = "$cwd/$namespace_path";
			// vendor/package.original already exists
			$path_original = $p.'.original';
			if (\is_dir($path_original)) {
				$this->error("Directory already exists: $path_original", __FILE__, __LINE__);
				exit(1);
			}
			// rename to dir.original
			if ( ! \rename($p, $path_original) ) {
				$this->error("Failed to rename directory: $namespace_path", __FILE__, __LINE__);
				exit(1);
			}
			// make symlink to local dev
			\symlink(
				\realpath("$cwd/$dev_path"),
				$p
			);
		}
	}



	public function restore() {
		// dev paths
		$paths = $this->config->getPaths();
		$cwd = \getcwd();
		if (empty($cwd))
			exit(1);
		foreach ($paths as $namespace => $dev_path) {
			$namespace_path = self::VendorPathFromNamespace($namespace);
			$p = "$cwd/$namespace_path";
			// remove symlink
			if (\is_link($p)) {
				if ( ! \unlink($p) ) {
					$this->error("Failed to remove symlink: $namespace_path", __FILE__, __LINE__);
					exit(1);
				}
			}
			// restore vendor
			if ( ! \is_dir($p) ) {
				if (\is_dir($p.'.original')) {
					if ( ! \rename($p.'.original', $p) ) {
						$this->error("Failed to rename directory: {$namespace_path}.original", __FILE__, __LINE__);
						exit(1);
					}
					$this->info("Restored vendor: <info>$namespace_path</info>");
				}
			}
		}
	}



	public static function VendorPathFromNamespace(string $namespace): string {
		$path = \str_replace('\\', '/', $namespace);
		return 'vendor/'.$path;
	}



	public function getInput() {
		$reflectObject = new \ReflectionObject($this->io);
		$reflectProperty = $reflectObject->getProperty('input');
		$reflectProperty->setAccessible(true);
		return $reflectProperty->getValue($this->io);
	}



	public function isDev(): bool {
		$input = $this->getInput();
		if ($input->hasOption('dev')) {
			if ($input->getOption('dev'))
				return true;
		}
		if ($input->hasOption('no-dev')) {
			if ($input->getOption('no-dev'))
				return false;
		}
		return ($this->config->isDev() != false);
	}



	public function info(string $msg): void {
		$this->io->writeError('[LocalDev] '.$msg);
	}
	public function debug(string $msg, string $_file=null, int $_line=-1): void {
		if ($_file === null || $_line === null) {
			$this->io->debug('[LocalDev] '.$msg);
		} else {
			$this->io->debug("[LocalDev] $_file:$_line - $msg");
		}
	}
	public function error(string $msg, string $_file, int $_line): void {
		$this->io->error("[LocalDev] $_file:$_line - $msg");
	}



	public static function dump($var): void {
		echo "--DUMP--\n";
		\var_dump($var);
		echo "--------\n";
	}



}
