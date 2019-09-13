<?php
/*
 * PoiXson Composer Local Dev - Links vendor data to a local workspace
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

	protected $composer;
	protected $io;
	protected $repoManager;

	protected $config;
	protected $isDev;



	public function activate(Composer $composer, IOInterface $io) {
		$this->composer = $composer;
		$this->io = $io;
		$this->repoManager = $composer->getRepositoryManager();
		// load config
		$this->config = new Config('../localdev.json');
		$this->config->load();
		$this->isDev = $this->config->isDev();
		if ($this->isDev) {
			$this->info('<info>Development mode</info>');
		}
	}



	public static function getSubscribedEvents(): array {
		return [
			ScriptEvents::PRE_INSTALL_CMD  => ['handleEvent', \PHP_INT_MAX],
			ScriptEvents::PRE_UPDATE_CMD   => ['handleEvent', \PHP_INT_MAX],
			ScriptEvents::POST_INSTALL_CMD => ['handleEvent', \PHP_INT_MIN],
			ScriptEvents::POST_UPDATE_CMD  => ['handleEvent', \PHP_INT_MIN]
		];
	}
	public function handleEvent($scriptEvent) {
		$eventName = $scriptEvent->getName();
		switch ($eventName) {
		case ScriptEvents::PRE_INSTALL_CMD:
		case ScriptEvents::PRE_UPDATE_CMD:
			$this->restore();
			break;
		case ScriptEvents::POST_INSTALL_CMD:
		case ScriptEvents::POST_UPDATE_CMD:
			$this->apply();
			break;
		default:
			$this->error("Unknown event: $eventName", __FILE__, __LINE__);
			exit(1);
		}
	}



	public function apply() {
		if ( ! $this->isDev() ) {
			$this->info('<info>Skipping symlinking</info>');
			return;
		}
/*
		$optimize = FALSE;
		{
			$input  = $this->getInput();
			$composerConfig = $this->composer->getConfig();
			// classmap-authoritative
			if ($input->getOption('classmap-authoritative')) {
				$this->info('<info>classmap-authoritative enabled by console</info>');
				$optimize = TRUE;
			} else
			if ($composerConfig->get('classmap-authoritative')) {
				$this->info('<info>classmap-authoritative enabled by composer config</info>');
				$optimize = TRUE;
			}
			// optimize-autoloader
			if ($input->getOption('optimize-autoloader')) {
				$this->info('<info>optimize-autoloader enabled by console</info>');
				$optimize = TRUE;
			} else
			if ($composerConfig->get('optimize-autoloader')) {
				$this->info('<info>optimize-autoloader enabled by composer config</info>');
				$optimize = TRUE;
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
		foreach ($paths as $namespace => $devPath) {
			if (empty($devPath)) continue;
			// check dev path exists
			if ( ! \is_dir("$cwd/$devPath") ) continue;
			$namespacePath = self::vendorPathFromNamespace($namespace);
			// check vendor path exists
			if ( ! \is_dir("$cwd/$namespacePath") ) continue;
			if ($first) {
				$first = FALSE;
				$this->info("<info>Creating symlinks to local dev..</info>");
			}
			$this->info("Symlinking.. <info>$namespacePath</info> => <info>$devPath</info>");
			$p = "$cwd/$namespacePath";
			// vendor/package.original already exists
			if (\is_dir("$p.original")) {
				$this->error("Directory already exists: $p.original", __FILE__, __LINE__);
				exit(1);
			}
			// rename to dir.original
			if ( ! \rename($p, "$p.original") ) {
				$this->error("Failed to rename directory: $namespacePath", __FILE__, __LINE__);
				exit(1);
			}
			// make symlink to local dev
			\symlink(
				\realpath("$cwd/$devPath"),
				$p
			);
		}
	}



	public function restore() {
		// dev paths
		$paths = $this->config->getPaths();
		$cwd = \getcwd();
		if (empty($cwd)) exit(1);
		foreach ($paths as $namespace => $devPath) {
			$namespacePath = self::vendorPathFromNamespace($namespace);
			$p = "$cwd/$namespacePath";
			// remove symlink
			if (\is_link($p)) {
				if ( ! \unlink($p) ) {
					$this->error("Failed to remove symlink: $namespacePath", __FILE__, __LINE__);
					exit(1);
				}
			}
			// restore vendor
			if ( ! \is_dir($p) ) {
				if (\is_dir("$p.original")) {
					if ( ! \rename("$p.original", $p) ) {
						$this->error("Failed to rename directory: $namespacePath.original", __FILE__, __LINE__);
						exit(1);
					}
					$this->info("Restored vendor: <info>$namespacePath</info>");
				}
			}
		}
	}



	public static function vendorPathFromNamespace($namespace) {
		$path = \str_replace('\\', '/', $namespace);
		return "vendor/$path";
	}



	public function getInput() {
		$reflectObject = new \ReflectionObject($this->io);
		$reflectProperty = $reflectObject->getProperty('input');
		$reflectProperty->setAccessible(TRUE);
		return $reflectProperty->getValue($this->io);
	}



	public function isDev() {
		return $this->isDev;
	}



	public function info($msg) {
		$this->io->writeError("[LocalDev] $msg");
	}
	public function debug($msg, $_file=NULL, $_line=NULL) {
		if ($_file === NULL || $_line === NULL) {
			$this->io->debug("[LocalDev] $msg");
		} else {
			$this->io->debug("[LocalDev] $_file:$_line - $msg");
		}
	}
	public function error($msg, $_file, $_line) {
		$this->io->error("[LocalDev] $_file:$_line - $msg");
	}



	public static function dump($var) {
		echo "--DUMP--\n";
		\var_dump($var);
		echo "--------\n";
	}



}
