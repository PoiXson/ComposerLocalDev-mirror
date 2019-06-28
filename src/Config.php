<?php
/*
 * PoiXson Composer Local Dev - Links vendor data to a local workspace
 * @copyright 2019
 * @license GPL-3
 * @author lorenzo at poixson.com
 * @link http://poixson.com/
 */
namespace pxn\ComposerLocalDev;



class Config {

	protected $configFile;
	protected $paths;



	public function __construct($configFile) {
		$this->configFile = $configFile;
	}



	public function getPaths() {
		return $this->paths;
	}
	public function setPaths(array $paths) {
		$this->paths = $paths;
	}



	public function load() {
		$data = $this->readFromFile();
		$this->paths = [];
		if (isset($data['paths'])) {
			$this->paths = $data['paths'];
		}
	}
	public function save() {
		$data = $this->readFromFile();
		$data['paths'] = $this->paths;
		$this->writeToFile($data);
	}



	protected function readFromFile() {
		return
			\json_decode(
				\file_get_contents($this->configFile),
				TRUE
			);
	}
	protected function writeToFile(array $data) {
		\file_put_contents(
			$this->configFile,
			\json_encode(
				$data,
				\JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE
			)
		);
	}



}
