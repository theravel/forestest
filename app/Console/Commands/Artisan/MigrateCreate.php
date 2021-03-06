<?php namespace Forestest\Console\Commands\Artisan;

use Illuminate\Foundation\Composer;
use Illuminate\Database\Console\Migrations\BaseCommand;
use Symfony\Component\Console\Input\InputArgument;

use Forestest\Console\Migrations\Generator;

class MigrateCreate extends BaseCommand {

	protected $name = 'migrate:create';
	protected $description = 'Create project-specific migration';

	/**
	 * @var \Forestest\Console\Migrations\Generator
	 */
	protected $generator;

	/**
	 * @var \Illuminate\Foundation\Composer
	 */
	protected $composer;

	protected function getArguments()
	{
		return [
			['name', InputArgument::REQUIRED, 'Migration name'],
		];
	}

	public function __construct(Generator $generator, Composer $composer)
	{
		parent::__construct();
		$this->generator = $generator;
		$this->composer = $composer;
	}

	public function fire()
	{
		$name = $this->input->getArgument('name');
		$path = $this->getMigrationPath();
		$file = pathinfo($this->generator->create($name, $path), PATHINFO_FILENAME);
		$this->line("<info>Created Migration:</info> $file");
		$this->composer->dumpAutoloads();
	}

}