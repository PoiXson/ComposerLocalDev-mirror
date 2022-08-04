<?php
/*
 * PoiXson ComposerLocalDev - Symlink vendor data to your local workspace
 * @copyright 2019-2022
 * @license GPL-3
 * @author lorenzo at poixson.com
 * @link https://poixson.com/
 */
namespace pxn\ComposerLocalDev;


class Config {

	protected string $config_file;
	protected array  $paths = [];
	protected int    $depth = 0;



	public function __construct(string $config_file, int $depth=0) {
		$this->config_file = $config_file;
		$this->depth = ($depth < 0 ? 0 : $depth);
	}



	public function getConfigPath(): string {
		return $this->config_file;
	}



	public function getPaths(): array {
		return $this->paths;
	}
	public function setPaths(array $paths): void {
		$this->paths = $paths;
	}



	public function getDepth(): int {
		return $this->depth;
	}



	public function isDev(): bool {
		return (\count($this->paths) > 0);
	}



	public function load(): void {
		$this->paths = [];
		if (empty($this->config_file))
			return;
		if ( ! \is_file($this->config_file) )
			return;
		$data = \file_get_contents($this->config_file);
		if ($data === false)
			return;
		$array = \json_decode(json: $data, associative: true);
		if ($array === false)
			return;
		if (isset($array['paths'])) {
			foreach ($array['paths'] as $namespace => $dev_path) {
				$this->paths[$namespace] = \str_repeat('../', $this->depth).$dev_path;
			}
		}
	}



}
