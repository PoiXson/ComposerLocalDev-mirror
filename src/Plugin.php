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

use pxn\ComposerLocalDev\Config;



class Plugin implements PluginInterface, EventSubscriberInterface {

	protected $composer;
	protected $io;
	protected $repoManager;

	protected $config;



	public function activate(Composer $composer, IOInterface $io) {
		$this->composer = $composer;
		$this->io = $io;
		$this->repoManager = $composer->getRepositoryManager();
		// load config
		$this->config = new Config('../localdev.json');
		$this->config->load();
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
		// dev/production
		if ( ! $this->composer->getPackage()->isDev() ) {
			$this->info('Skipping symlinking due to: not dev');
			return;
		}
		// optimize-autoloader
		$optimize = FALSE;
		{
			$input  = $this->getInput();
			$config = $this->composer->getConfig();
			if ($input->getOption('optimize-autoloader')) {
				$this->debug('optimize-autoloader enabled by console', __FILE__, __LINE__);
				$optimize = TRUE;
			} else
			if ($config->get('optimize-autoloader')) {
				$this->debug('optimize-autoloader enabled by config', __FILE__, __LINE__);
				$optimize = TRUE;
			}
			$this->debug('Optimize: '.($optimize ? 'yes' : 'no'), __FILE__, __LINE__);
			if ($optimize) {
				$this->info('Skipping symlinking due to: optimize');
				return;
			}
		}
		// dev paths
		$paths = $this->config->getPaths();
		$cwd = \getcwd();
		foreach ($paths as $namespace => $devPath) {
			if (empty($devPath)) continue;
			if ( ! \is_dir("$cwd/$devPath") ) continue;
			$namespacePath = self::vendorPathFromNamespace($namespace);
			$this->info("Found local dev, making symlink.. $namespacePath => $devPath");
			$p = "$cwd/$namespacePath";
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
				$this->info("Removed symlink, restoring vendor.. $namespacePath");
			}
			// restore vendor
			if ( ! \is_dir($p) ) {
				if (\is_dir("$p.original")) {
					if ( ! \rename("$p.original", $p) ) {
						$this->error("Failed to rename directory: $namespacePath.original", __FILE__, __LINE__);
						exit(1);
					}
					$this->info("Restored vendor: $namespacePath");
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



	public function info($msg) {
		$this->io->writeError("<info> [LocalDev] $msg</info>");
	}
	public function debug($msg, $_file, $_line) {
		$this->io->debug(" [LocalDev] $_file:$_line - $msg");
	}
	public function error($msg, $_file, $_line) {
		$this->io->error(" [LocalDev] $_file:$_line - $msg");
	}



	public static function dump($var) {
		echo "--DUMP--\n";
		\var_dump($var);
		echo "--------\n";
	}



}
